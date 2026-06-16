<!-- ========== Left Sidebar Start ========== -->
<div class="fixed bottom-0 z-10 h-screen ltr:border-r rtl:border-l vertical-menu rtl:right-0 ltr:left-0 top-[70px] pt-12 bg-slate-50 border-gray-50 print:hidden">
    <div data-simplebar class="h-full">
        <div class="metismenu pb-10 pt-2.5" id="sidebar-menu">
            <ul id="side-menu">
                <li>
                    <a href="{{ route('admin.index') }}" class="block py-2.5 px-6 text-sm font-medium transition-all duration-150 ease-linear {{ request()->routeIs('admin.index', 'admin.dashboard') ? 'text-blue-600' : 'text-gray-600 hover:text-blue-500' }}">
                        <i data-feather="home" fill="#545a6d33"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li class="px-5 py-3 text-xs font-medium text-gray-500 cursor-default leading-[18px] group-data-[sidebar-size=sm]:hidden block">
                    Lotto
                </li>

                <li>
                    <a href="{{ route('admin.settings') }}" class="block py-2.5 px-6 text-sm font-medium transition-all duration-150 ease-linear {{ request()->routeIs('admin.settings') ? 'text-blue-600' : 'text-gray-600 hover:text-blue-500' }}">
                        <i data-feather="settings" fill="#545a6d33"></i>
                        <span>Einstellungen</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('admin.history') }}" class="block py-2.5 px-6 text-sm font-medium transition-all duration-150 ease-linear {{ request()->routeIs('admin.history') ? 'text-blue-600' : 'text-gray-600 hover:text-blue-500' }}">
                        <i data-feather="clock" fill="#545a6d33"></i>
                        <span>Historie</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('admin.recommendations') }}" class="block py-2.5 px-6 text-sm font-medium transition-all duration-150 ease-linear {{ request()->routeIs('admin.recommendations') ? 'text-blue-600' : 'text-gray-600 hover:text-blue-500' }}">
                        <i data-feather="star" fill="#545a6d33"></i>
                        <span>Empfehlungen</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('admin.number-check') }}" class="block py-2.5 px-6 text-sm font-medium transition-all duration-150 ease-linear {{ request()->routeIs('admin.number-check') ? 'text-blue-600' : 'text-gray-600 hover:text-blue-500' }}">
                        <i data-feather="check-circle" fill="#545a6d33"></i>
                        <span>Zahlencheck</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
<!-- Left Sidebar End -->
