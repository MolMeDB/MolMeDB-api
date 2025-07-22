import { defineConfig } from "vite";
import laravel, { refreshPaths } from "laravel-vite-plugin";
// import tailwindcss from "@tailwindcss/vite";
import tailwindcss from "tailwindcss";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // "resources/sass/app.scss",
                "resources/css/app.css",
                "resources/js/app.js",
            ],
            refresh: [...refreshPaths, "app/Livewire/**"],
        }),
        tailwindcss(),
    ],
    css: {
        plugins: [tailwindcss()],
    },
});
