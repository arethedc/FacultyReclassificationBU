<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-800">My Profile</h2>
            <p class="text-sm text-gray-500">Profile details are locked. You can reset your password below.</p>
        </div>
    </x-slot>

    @php
        $user = auth()->user();
        $profile = $user?->facultyProfile;
        $highest = $user?->facultyHighestDegree?->highest_degree;
        $rankLabel = $profile?->rankLevel?->title
            ?: ($profile?->teaching_rank ?? '—');
    @endphp

    <div class="py-12 bg-bu-muted min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Account Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Full Name</span>
                        <div class="font-medium text-gray-800">{{ $user?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <span class="text-gray-500">Email</span>
                        <div class="font-medium text-gray-800">{{ $user?->email ?? '—' }}</div>
                    </div>
                    <div>
                        <span class="text-gray-500">Role</span>
                        <div class="font-medium text-gray-800">{{ ucfirst(str_replace('_',' ', $user?->role ?? '')) }}</div>
                    </div>
                    <div>
                        <span class="text-gray-500">Department</span>
                        <div class="font-medium text-gray-800">{{ $user?->department?->name ?? '—' }}</div>
                    </div>
                </div>
            </div>

            @if($user?->role === 'faculty')
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Faculty Profile (Read-only)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Employee No.</span>
                            <div class="font-medium text-gray-800">{{ $profile?->employee_no ?? '—' }}</div>
                        </div>
                        <div>
                            <span class="text-gray-500">Employment Type</span>
                            <div class="font-medium text-gray-800">
                                {{ $profile?->employment_type ? ucwords(str_replace('_',' ', $profile->employment_type)) : '—' }}
                            </div>
                        </div>
                        <div>
                            <span class="text-gray-500">Current Rank</span>
                            <div class="font-medium text-gray-800">{{ $rankLabel }}</div>
                        </div>
                        <div>
                            <span class="text-gray-500">Highest Degree</span>
                            <div class="font-medium text-gray-800">
                                {{ $highest ? ucfirst($highest) : '—' }}
                            </div>
                        </div>
                        <div>
                            <span class="text-gray-500">Original Appointment</span>
                            <div class="font-medium text-gray-800">
                                {{ $profile?->original_appointment_date?->format('M d, Y') ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Reset Password</h3>
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
