<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const isLocalDev =
    window.location.hostname === 'localhost' ||
    window.location.hostname === '127.0.0.1' ||
    window.location.hostname.endsWith('.test') ||
    window.location.hostname.endsWith('.herd.run');
</script>

<template>
    <Head title="Log in" />

    <div class="flex min-h-screen bg-stone-100 antialiased">
        <!-- Left Panel: Branding & Visual -->
        <div
            class="relative hidden w-1/2 items-center justify-center bg-gradient-to-br from-amber-100 to-orange-100 p-12 lg:flex"
        >
            <!-- Subtle animated background pattern -->
            <div class="absolute inset-0 opacity-20">
                <svg
                    class="h-full w-full"
                    viewBox="0 0 100 100"
                    preserveAspectRatio="none"
                >
                    <path
                        d="M0,50 L100,50 M50,0 L50,100"
                        stroke="#d97706"
                        stroke-width="0.5"
                        stroke-dasharray="5 5"
                        class="animate-move-dash"
                    />
                </svg>
            </div>

            <!-- Animated work illustration -->
            <div class="relative z-10 text-center text-stone-700">
                <div class="mx-auto mb-8 h-40 w-40">
                    <svg
                        viewBox="0 0 200 200"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <!-- Office building with subtle pulse -->
                        <path
                            d="M50 50V150H150V50H50Z"
                            stroke="#92400e"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="animate-pulse-opacity"
                        />
                        <path
                            d="M70 70V100H90V70H70Z"
                            stroke="#b45309"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="animate-pulse-opacity"
                            style="animation-delay: 0.3s"
                        />
                        <path
                            d="M110 70V100H130V70H110Z"
                            stroke="#b45309"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="animate-pulse-opacity"
                            style="animation-delay: 0.6s"
                        />

                        <!-- Progress bars with continuous growing/shrinking animation -->
                        <rect
                            x="30"
                            y="30"
                            width="10"
                            height="20"
                            fill="#f59e0b"
                            fill-opacity="0.7"
                            class="animate-graph-pulse"
                            style="
                                transform-origin: bottom;
                                animation-delay: 0.4s;
                            "
                        />
                        <rect
                            x="45"
                            y="40"
                            width="10"
                            height="10"
                            fill="#fbbf24"
                            fill-opacity="0.5"
                            class="animate-graph-pulse"
                            style="
                                transform-origin: bottom;
                                animation-delay: 0.6s;
                            "
                        />
                        <rect
                            x="60"
                            y="20"
                            width="10"
                            height="30"
                            fill="#fcd34d"
                            fill-opacity="0.9"
                            class="animate-graph-pulse"
                            style="
                                transform-origin: bottom;
                                animation-delay: 0.8s;
                            "
                        />

                        <!-- Clock with ticking animation -->
                        <circle
                            cx="170"
                            cy="40"
                            r="15"
                            stroke="#92400e"
                            stroke-width="1.5"
                            class="animate-rotate"
                            style="
                                transform-origin: 170px 40px;
                                animation-delay: 1s;
                            "
                        />
                        <path
                            d="M170 40V30"
                            stroke="#92400e"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            class="animate-rotate"
                            style="transform-origin: 170px 40px"
                        />
                        <path
                            d="M170 40H175"
                            stroke="#b45309"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            class="animate-rotate-fast"
                            style="
                                transform-origin: 170px 40px;
                                animation-delay: 0.5s;
                            "
                        />
                    </svg>
                </div>

                <h1
                    class="animate-fade-in mb-3 text-4xl font-bold text-amber-800"
                >
                    WorkSphere
                </h1>
                <p
                    class="animate-fade-in mx-auto max-w-md text-lg text-stone-600 opacity-90"
                    style="animation-delay: 0.3s"
                >
                    The modern way to manage your team's productivity
                </p>
            </div>
        </div>

        <!-- Right Panel: Login Form -->
        <div
            class="flex w-full items-center justify-center bg-stone-50 p-6 sm:p-12 lg:w-1/2"
        >
            <div
                class="animate-fade-in w-full max-w-md"
                style="animation-delay: 0.2s"
            >
                <div class="mb-8 text-center lg:text-left">
                    <h2 class="text-2xl font-bold text-stone-800">
                        Welcome back
                    </h2>
                    <p class="mt-2 text-stone-600">
                        Sign in to continue to your dashboard
                    </p>
                </div>

                <div
                    v-if="status"
                    class="mb-4 text-sm font-medium text-amber-600"
                >
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <InputLabel
                            for="email"
                            value="Email"
                            class="mb-1 text-stone-700"
                        />
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full rounded-md border-stone-300 shadow-sm focus:border-amber-400 focus:ring focus:ring-amber-200 focus:ring-opacity-50"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="you@company.com"
                        />
                        <InputError class="mt-1" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel
                            for="password"
                            value="Password"
                            class="mb-1 text-stone-700"
                        />
                        <TextInput
                            id="password"
                            type="password"
                            class="mt-1 block w-full rounded-md border-stone-300 shadow-sm focus:border-amber-400 focus:ring focus:ring-amber-200 focus:ring-opacity-50"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        />
                        <InputError
                            class="mt-1"
                            :message="form.errors.password"
                        />
                    </div>
                    <!-- Only visible in local/dev -->
                    <div v-if="isLocalDev" class="mt-8">
                        <p class="mb-2 text-xs text-white/70">
                            Dev quick login:
                        </p>
                        <div class="flex flex-wrap justify-center gap-2">
                            <a
                                v-for="role in [
                                    'admin',
                                    'hr',
                                    'project-manager',
                                    'team-lead',
                                    'employee',
                                ]"
                                :key="role"
                                :href="`/dev-login/${role}`"
                                class="rounded bg-gray-800 px-3 py-1 text-xs text-white transition hover:bg-blue-700 focus:ring-2 focus:ring-blue-300"
                            >
                                Login as
                                {{
                                    role
                                        .replace('-', ' ')
                                        .replace(/\b\w/g, (l) =>
                                            l.toUpperCase(),
                                        )
                                }}
                            </a>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <Checkbox
                                name="remember"
                                v-model:checked="form.remember"
                                class="rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-500"
                            />
                            <span class="ml-2 text-sm text-stone-600"
                                >Remember me</span
                            >
                        </label>

                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm font-medium text-amber-600 hover:text-amber-500"
                        >
                            Forgot password?
                        </Link>
                    </div>

                    <div>
                        <PrimaryButton
                            class="w-full justify-center bg-amber-600 py-2.5 font-semibold text-white hover:bg-amber-700 focus:bg-amber-700 focus:ring-amber-500 active:bg-amber-800"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Sign In
                        </PrimaryButton>
                    </div>
                </form>

                <p class="mt-8 text-center text-xs text-stone-500">
                    © {{ new Date().getFullYear() }} WorkSphere. All rights
                    reserved.
                </p>
            </div>
        </div>
    </div>
</template>

<style>
/* Keyframes remain the same as they're animation definitions */
@keyframes move-dash {
    to {
        stroke-dashoffset: 10;
    }
}

@keyframes pulse-opacity {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.8;
    }
}

@keyframes graph-pulse {
    0%,
    100% {
        transform: scaleY(1);
    }
    25% {
        transform: scaleY(0.98);
    }
    50% {
        transform: scaleY(1.05);
    }
    75% {
        transform: scaleY(0.97);
    }
}

@keyframes rotate {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

@keyframes rotate-fast {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(3600deg);
    }
}

@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-move-dash {
    animation: move-dash 3s linear infinite;
}

.animate-pulse-opacity {
    animation: pulse-opacity 4s ease-in-out infinite;
}

.animate-graph-pulse {
    animation: graph-pulse 6s ease-in-out infinite;
}

.animate-rotate {
    animation: rotate 60s linear infinite;
}

.animate-rotate-fast {
    animation: rotate-fast 10s linear infinite;
}

.animate-fade-in {
    animation: fade-in 0.8s ease-out forwards;
}
</style>
