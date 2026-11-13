<nav class="segmented" aria-label="Affichage">
    <a href="{{ route('agent.route') }}" class="{{ request()->routeIs('agent.route') ? 'active' : '' }}">Aujourd'hui</a>
    <a href="{{ route('agent.planning') }}" class="{{ request()->routeIs('agent.planning') ? 'active' : '' }}">7 jours</a>
</nav>
