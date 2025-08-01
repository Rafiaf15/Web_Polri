    <nav class="sidebar bg-danger d-flex flex-column p-3 text-white" style="width:260px;min-height:100vh;">
        <div class="mb-4 text-center">
            <a href="{{ route('dashboard') }}" class="text-decoration-none text-white">
                <img src="/img/logo.png" alt="Logo" style="height:48px;">
                <h5 class="mt-2 fw-bold">{{ __('messages.divtik_title') }}</h5>
            </a>
        </div>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item mb-2">
                <a href="{{ route('dashboard') }}" class="nav-link text-white {{ request()->routeIs('dashboard') ? 'active bg-white-50' : '' }}" style="font-weight:600;color:#ffffff !important;">{{ __('messages.dashboard') }}</a>
            </li>
            <li class="mb-2">
                <a href="#" class="nav-link text-white {{ request()->routeIs('schedule.*') ? 'active bg-white-50' : '' }}" data-bs-toggle="collapse" data-bs-target="#coverLetterMenu" aria-expanded="{{ request()->routeIs('schedule.*') ? 'true' : 'false' }}" aria-controls="coverLetterMenu">{{ __('messages.schedule') }}</a>
                <ul class="collapse list-unstyled ps-3 {{ request()->routeIs('schedule.*') ? 'show' : '' }}" id="coverLetterMenu">
                    <li><a href="{{ route('schedule.index') }}" class="nav-link text-white-50 {{ request()->routeIs('schedule.index') ? 'active bg-white-50' : '' }}">Jadwal Edit</a></li>
                    <li><a href="{{ route('schedule.piket') }}" class="nav-link text-white-50 {{ request()->routeIs('schedule.piket') ? 'active bg-white-50' : '' }}">Jadwal Piket</a></li>
                </ul>
            </li>
        </ul>
        <div class="mt-auto text-center small" style="color:#fff;opacity:.7;">
            &copy; 2025 All Rights Reserved
        </div>
    </nav> 