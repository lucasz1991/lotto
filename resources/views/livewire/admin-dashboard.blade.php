<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Lotto</h1>
            <p class="mt-1 text-sm text-gray-500">
                Admin-Dashboard
            </p>
        </div>
        <a href="{{ route('admin.history') }}" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            Historie
        </a>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Benutzer</p>
            <p class="mt-3 text-3xl font-semibold text-gray-900">{{ $totalUsers }}</p>
        </div>
    </div>
</div>
