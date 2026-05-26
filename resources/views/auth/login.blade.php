<x-guest-layout>

    @if (session('status'))
        <div class="mb-4 text-sm text-green-600">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded bg-red-100 p-3 text-red-700">
            <ul class="list-disc pr-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        
        @csrf

        <!-- Phone -->
        <div>
            <label for="phone" class="block mb-1 text-sm font-medium text-gray-700">
                شماره تلفن
            </label>

            <input
                id="phone"
                type="text"
                name="phone"
                value="{{ old('phone') }}"
                required
                autofocus
                autocomplete="username"
                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
            >
        </div>

        <!-- Password -->
        <div class="mt-4">
            <label for="password" class="block mb-1 text-sm font-medium text-gray-700">
                رمز عبور
            </label>

            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
            >
        </div>

        <!-- Remember -->
        <div class="mt-4 flex items-center">
            <input
                id="remember_me"
                type="checkbox"
                name="remember"
                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
            >

            <label for="remember_me" class="mr-2 text-sm text-gray-600">
                مرا به خاطر بسپار
            </label>
        </div>

        <!-- Submit -->
        <div class="mt-6">
            <button
                type="submit"
                class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700 transition"
            >
                ورود
            </button>
        </div>

    </form>

</x-guest-layout>