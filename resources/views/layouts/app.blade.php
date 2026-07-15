<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Beranda') — SIM-KERMA</title>
    <meta name="description" content="Sistem Administrasi Kerjasama - Manajemen dokumen kerjasama universitas" />

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

    <!-- FontAwesome (Bundled via Vite) -->

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-800 h-screen overflow-hidden flex flex-col font-[Inter]">
    <div id="app" class="flex-1 flex h-full relative">
        <!-- Sidebar -->
        @include('components.sidebar')

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            @include('components.header')

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-8">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Modal Container (Alpine.js) -->
    <div x-data="{ open: false, title: '', body: '' }"
         x-show="open"
         x-cloak
         @open-modal.window="open = true; title = $event.detail.title; body = $event.detail.body"
         @close-modal.window="open = false"
         @keydown.escape.window="open = false"
         class="relative z-50"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-500/75 transition-opacity" @click="open = false"></div>
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6">
                        <h3 class="text-lg font-bold text-slate-900 mb-4" x-text="title"></h3>
                        <div x-html="body"></div>
                    </div>
                    <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button @click="open = false" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div x-data="toastNotification()" @toast.window="addToast($event.detail)" class="fixed top-4 right-4 z-[60] space-y-2">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="toast.show"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-x-8"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg border min-w-[300px]"
                 :class="{
                     'bg-green-50 border-green-200 text-green-800': toast.type === 'success',
                     'bg-red-50 border-red-200 text-red-800': toast.type === 'error',
                     'bg-yellow-50 border-yellow-200 text-yellow-800': toast.type === 'warning',
                     'bg-blue-50 border-blue-200 text-blue-800': toast.type === 'info',
                 }">
                <i class="fa-solid" :class="{
                    'fa-check-circle text-green-500': toast.type === 'success',
                    'fa-times-circle text-red-500': toast.type === 'error',
                    'fa-exclamation-triangle text-yellow-500': toast.type === 'warning',
                    'fa-info-circle text-blue-500': toast.type === 'info',
                }"></i>
                <span class="text-sm font-medium" x-text="toast.message"></span>
            </div>
        </template>
    </div>

    @stack('scripts')

    @if(session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { type: 'error', message: @json(session('error')) }
            }));
        });
    </script>
    @endif
    @if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { type: 'success', message: @json(session('success')) }
            }));
        });
    </script>
    @endif
    <script>
        function toastNotification() {
            return {
                toasts: [],
                addToast(detail) {
                    const toast = { id: Date.now(), show: true, ...detail };
                    this.toasts.push(toast);
                    setTimeout(() => { toast.show = false; }, 3000);
                    setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== toast.id); }, 3500);
                }
            }
        }
    </script>
</body>
</html>
