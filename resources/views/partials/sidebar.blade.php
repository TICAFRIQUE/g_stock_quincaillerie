<nav class="nav flex-column gap-1">
    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <i class="bi bi-speedometer2 me-2"></i>Tableau de bord
    </a>

    @canany(['caisse.ouvrir', 'client.voir', 'devis.voir'])
        <span class="text-uppercase small px-2 mt-3 mb-1" style="color: var(--erp-sidebar-color); opacity: .6;">Ventes</span>
        @can('caisse.ouvrir')
            <a href="{{ route('sessions.index') }}" class="nav-link {{ request()->routeIs('sessions.*', 'ventes.*', 'ventes-en-attente.*', 'reglements.*') ? 'active' : '' }}">
                <i class="bi bi-cash-stack me-2"></i>Caisses
            </a>
        @endcan
        @can('devis.voir')
            <a href="{{ route('devis.index') }}" class="nav-link {{ request()->routeIs('devis.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text me-2"></i>Devis
            </a>
        @endcan
        @can('client.voir')
            <a href="{{ route('clients.index') }}" class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }}">
                <i class="bi bi-people-fill me-2"></i>Clients
            </a>
        @endcan
    @endcanany

    @canany(['stock.voir', 'inventaire.voir', 'achat.voir', 'fournisseur.gerer'])
        <span class="text-uppercase small px-2 mt-3 mb-1" style="color: var(--erp-sidebar-color); opacity: .6;">Stock</span>
        @can('stock.voir')
            <a href="{{ route('stock.index') }}" class="nav-link {{ request()->routeIs('stock.*', 'transferts.*') ? 'active' : '' }}">
                <i class="bi bi-boxes me-2"></i>Stock
            </a>
        @endcan
        @can('inventaire.voir')
            <a href="{{ route('inventaires.index') }}" class="nav-link {{ request()->routeIs('inventaires.*') ? 'active' : '' }}">
                <i class="bi bi-clipboard-check me-2"></i>Inventaire
            </a>
        @endcan
         @can('fournisseur.gerer')
            <a href="{{ route('fournisseurs.index') }}" class="nav-link {{ request()->routeIs('fournisseurs.*') ? 'active' : '' }}">
                <i class="bi bi-building me-2"></i>Fournisseurs
            </a>
        @endcan

        @can('achat.voir')
            <a href="{{ route('commande-achats.index') }}" class="nav-link {{ request()->routeIs('commande-achats.*') ? 'active' : '' }}">
                <i class="bi bi-truck me-2"></i>Achats
            </a>
        @endcan
       
    @endcanany

    @canany(['produit.voir', 'categorie.gerer'])
        <span class="text-uppercase small px-2 mt-3 mb-1" style="color: var(--erp-sidebar-color); opacity: .6;">Catalogue</span>
         @can('categorie.gerer')
            <a href="{{ route('categories.index') }}" class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                <i class="bi bi-tags me-2"></i>Catégories
            </a>
        @endcan
        @can('produit.voir')
            <a href="{{ route('produits.index') }}" class="nav-link {{ request()->routeIs('produits.*') ? 'active' : '' }}">
                <i class="bi bi-cup-hot me-2"></i>Produits
            </a>
        @endcan
      
    @endcanany

    @can('rapport.voir')
        <span class="text-uppercase small px-2 mt-3 mb-1" style="color: var(--erp-sidebar-color); opacity: .6;">Rapports</span>
        <a href="{{ route('rapports.index') }}" class="nav-link {{ request()->routeIs('rapports.*') ? 'active' : '' }}">
            <i class="bi bi-bar-chart-line me-2"></i>Rapports
        </a>
    @endcan

    @canany(['administration.gerer', 'parametre.gerer', 'utilisateur.gerer', 'role.gerer'])
        <span class="text-uppercase small px-2 mt-3 mb-1" style="color: var(--erp-sidebar-color); opacity: .6;">Administration</span>

        @can('administration.gerer')
            <a href="{{ route('magasins.index') }}" class="nav-link {{ request()->routeIs('magasins.*') ? 'active' : '' }}">
                <i class="bi bi-shop me-2"></i>Magasins
            </a>
            <a href="{{ route('caisses.index') }}" class="nav-link {{ request()->routeIs('caisses.*') ? 'active' : '' }}">
                <i class="bi bi-cash-stack me-2"></i>Caisses
            </a>
            <a href="{{ route('moyens-paiement.index') }}" class="nav-link {{ request()->routeIs('moyens-paiement.*') ? 'active' : '' }}">
                <i class="bi bi-credit-card me-2"></i>Moyens de paiement
            </a>
            <a href="{{ route('unites.index') }}" class="nav-link {{ request()->routeIs('unites.*') ? 'active' : '' }}">
                <i class="bi bi-rulers me-2"></i>Unités
            </a>
        @endcan

        @can('parametre.gerer')
            <a href="{{ route('parametres.edit') }}" class="nav-link {{ request()->routeIs('parametres.*') ? 'active' : '' }}">
                <i class="bi bi-gear me-2"></i>Paramètres
            </a>
        @endcan

        @can('utilisateur.gerer')
            <a href="{{ route('utilisateurs.index') }}" class="nav-link {{ request()->routeIs('utilisateurs.*') ? 'active' : '' }}">
                <i class="bi bi-people me-2"></i>Utilisateurs
            </a>
        @endcan

        @can('role.gerer')
            <a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                <i class="bi bi-shield-lock me-2"></i>Rôles
            </a>
        @endcan
    @endcanany
</nav>
