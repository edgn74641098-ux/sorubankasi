<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-sky-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase hover:bg-sky-900 focus:bg-sky-900 active:bg-sky-950 focus:outline-none focus:ring-2 focus:ring-sky-700 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
