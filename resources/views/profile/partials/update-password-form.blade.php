<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post"
          action="{{ route('password.update') }}"
          class="mt-6 space-y-6"
          x-data="{
              showCurrent: false,
              showNew: false,
              showConfirm: false,
              newPassword: '',
              confirmPassword: '',
              hasMismatch() {
                  return this.newPassword !== '' && this.confirmPassword !== '' && this.newPassword !== this.confirmPassword;
              }
          }">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            <div class="relative mt-1">
                <x-text-input id="update_password_current_password"
                              name="current_password"
                              x-bind:type="showCurrent ? 'text' : 'password'"
                              class="block w-full pr-10"
                              autocomplete="current-password" />
                <button type="button"
                        @click="showCurrent = !showCurrent"
                        class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-bu focus:ring-offset-1 rounded-md"
                        :aria-label="showCurrent ? 'Hide current password' : 'Show current password'">
                    <svg x-show="!showCurrent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <svg x-show="showCurrent" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.73 5.08A10.45 10.45 0 0 1 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639a10.477 10.477 0 0 1-1.61 3.04M6.228 6.228A10.451 10.451 0 0 0 2.037 11.68a1.012 1.012 0 0 0 0 .639C3.423 16.49 7.36 19.5 12 19.5a10.45 10.45 0 0 0 5.772-1.728M9.88 9.88a3 3 0 1 0 4.243 4.243" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" />
            <div class="relative mt-1">
                <x-text-input id="update_password_password"
                              name="password"
                              x-model="newPassword"
                              x-bind:type="showNew ? 'text' : 'password'"
                              class="block w-full pr-10"
                              autocomplete="new-password" />
                <button type="button"
                        @click="showNew = !showNew"
                        class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-bu focus:ring-offset-1 rounded-md"
                        :aria-label="showNew ? 'Hide new password' : 'Show new password'">
                    <svg x-show="!showNew" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <svg x-show="showNew" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.73 5.08A10.45 10.45 0 0 1 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639a10.477 10.477 0 0 1-1.61 3.04M6.228 6.228A10.451 10.451 0 0 0 2.037 11.68a1.012 1.012 0 0 0 0 .639C3.423 16.49 7.36 19.5 12 19.5a10.45 10.45 0 0 0 5.772-1.728M9.88 9.88a3 3 0 1 0 4.243 4.243" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
            <div class="relative mt-1">
                <x-text-input id="update_password_password_confirmation"
                              name="password_confirmation"
                              x-model="confirmPassword"
                              x-bind:type="showConfirm ? 'text' : 'password'"
                              class="block w-full pr-10"
                              autocomplete="new-password" />
                <button type="button"
                        @click="showConfirm = !showConfirm"
                        class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-bu focus:ring-offset-1 rounded-md"
                        :aria-label="showConfirm ? 'Hide confirm password' : 'Show confirm password'">
                    <svg x-show="!showConfirm" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <svg x-show="showConfirm" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.73 5.08A10.45 10.45 0 0 1 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639a10.477 10.477 0 0 1-1.61 3.04M6.228 6.228A10.451 10.451 0 0 0 2.037 11.68a1.012 1.012 0 0 0 0 .639C3.423 16.49 7.36 19.5 12 19.5a10.45 10.45 0 0 0 5.772-1.728M9.88 9.88a3 3 0 1 0 4.243 4.243" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
            <p x-show="hasMismatch()" x-cloak class="mt-2 text-sm text-red-600">
                New password and confirmation must match.
            </p>
        </div>

        <div class="flex items-center gap-4">
            <button type="submit"
                    :disabled="hasMismatch()"
                    :class="hasMismatch() ? 'opacity-60 cursor-not-allowed' : ''"
                    class="px-5 py-2.5 rounded-xl bg-green-600 text-white text-sm font-semibold hover:bg-green-700 shadow-soft">
                {{ __('Reset Password') }}
            </button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
