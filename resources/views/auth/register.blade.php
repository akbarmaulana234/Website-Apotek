<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contact Us | HyperUI</title>

    {{-- Load Tailwind CSS via Laravel Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-white flex items-center justify-center px-4 py-12">

    <main class="w-full max-w-lg">

        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                Get in touch
            </h1>

            <p class="mt-3 text-sm leading-6 text-slate-400">
                Have a question or project in mind? Send us a message.
            </p>
        </div>

        @if ($errors->any())
            <span class="text-red-500">
                @foreach ($errors->all() as $item)
                {{ $item }}
                @endforeach
            </span>
        @endif
        
        <form
            action="/register/action"
            method="POST"
            class="space-y-5 rounded-2xl border border-white/10 bg-white p-6 shadow-2xl sm:p-8">
            @csrf

            <div>
                <label
                    for="name"
                    class="block text-sm font-semibold text-slate-700">
                    Name
                </label>

                <input
                    id="name"
                    name="name"
                    type="name"
                    placeholder="Your name"
                    autocomplete="name"
                    required
                    class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
            </div>

            <div>
                <label
                    for="email"
                    class="block text-sm font-semibold text-slate-700">
                    Email address
                </label>

                <input
                    id="email"
                    name="email"
                    type="email"
                    placeholder="you@example.com"
                    autocomplete="email"
                    required
                    class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
            </div>
            <div>
                <label
                    for="password"
                    class="block text-sm font-semibold text-slate-700">
                    Password
                </label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    placeholder="******"
                    autocomplete="password"
                    required
                    class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
            </div>


            <button
                type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:scale-[0.99]">
                Send Message
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    class="h-4 w-4"
                    aria-hidden="true">

                </svg>
            </button>
        </form>



    </main>

</body>

</html>