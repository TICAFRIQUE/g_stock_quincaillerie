<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $roles = Role::withCount(['users', 'permissions'])
            ->when(! $request->user()->hasRole('Superadmin'), fn ($query) => $query->where('name', '!=', 'Superadmin'))
            ->orderBy('name')
            ->get();

        return view('roles.index', ['roles' => $roles]);
    }

    public function create(): View
    {
        return view('roles.create', ['permissionsParModule' => $this->permissionsParModule()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        $role = Role::create(['name' => $donnees['name'], 'guard_name' => 'web']);
        $permissions = $this->filtrerPermissionsAutorisees($donnees['permissions'] ?? []);
        $role->syncPermissions($permissions);

        activity()
            ->causedBy($request->user())
            ->performedOn($role)
            ->withProperties(['permissions' => $permissions])
            ->log("Rôle « {$role->name} » créé");

        return redirect()->route('roles.index')->with('succes', 'Rôle créé.');
    }

    public function edit(Role $role): View
    {
        abort_if($role->name === 'Superadmin', 403, 'Le rôle Superadmin ne se gère pas depuis cet écran.');

        return view('roles.edit', [
            'role' => $role,
            'permissionsParModule' => $this->permissionsParModule(),
            'permissionsActuelles' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->name === 'Superadmin', 403);

        $donnees = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,'.$role->id],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        // Le noyau protégé n'est jamais ajoutable/retirable depuis cet écran : on
        // préserve tel quel ce que le rôle possédait déjà parmi les permissions
        // protégées, quoi qu'il y ait dans le formulaire soumis.
        $protegeesConservees = $role->permissions->pluck('name')
            ->intersect(config('permissions.protected'))
            ->all();

        $permissionsAvant = $role->permissions->pluck('name')->all();

        $role->update(['name' => $donnees['name']]);
        $permissionsApres = [
            ...$this->filtrerPermissionsAutorisees($donnees['permissions'] ?? []),
            ...$protegeesConservees,
        ];
        $role->syncPermissions($permissionsApres);

        activity()
            ->causedBy($request->user())
            ->performedOn($role)
            ->withProperties(['avant' => $permissionsAvant, 'apres' => $permissionsApres])
            ->log("Rôle « {$role->name} » modifié");

        return redirect()->route('roles.index')->with('succes', 'Rôle mis à jour.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->name === 'Superadmin', 403);

        if ($role->users()->exists()) {
            return redirect()->route('roles.index')->with('erreur', 'Ce rôle est attribué à des utilisateurs, il ne peut pas être supprimé.');
        }

        activity()
            ->causedBy($request->user())
            ->performedOn($role)
            ->withProperties(['nom' => $role->name, 'permissions' => $role->permissions->pluck('name')->all()])
            ->log("Rôle « {$role->name} » supprimé");

        $role->delete();

        return redirect()->route('roles.index')->with('succes', 'Rôle supprimé.');
    }

    private function permissionsParModule(): array
    {
        $protegees = config('permissions.protected');

        return collect(config('permissions.catalogue'))
            ->reject(fn (string $permission) => in_array($permission, $protegees, true))
            ->groupBy(fn (string $permission) => explode('.', $permission)[0])
            ->all();
    }

    private function filtrerPermissionsAutorisees(array $permissions): array
    {
        $autorisees = array_diff(config('permissions.catalogue'), config('permissions.protected'));

        return array_values(array_intersect($permissions, $autorisees));
    }
}
