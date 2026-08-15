<div class="hidden md:flex items-center h-16 gap-1">
    <a href="{{ route('job.index') }}"
       class="h-16 inline-flex items-center px-4 text-sm font-medium border-b-2 {{ request()->routeIs('job.index') || request()->routeIs('job.show') ? 'border-[#2557a7] text-[#2557a7]' : 'border-transparent text-[#2d2d2d] hover:text-[#2557a7] hover:border-[#2557a7]' }}">
        Find jobs
    </a>
    <a href="{{ route('job.categories') }}"
       class="h-16 inline-flex items-center px-4 text-sm font-medium border-b-2 {{ request()->routeIs('job.categories') ? 'border-[#2557a7] text-[#2557a7]' : 'border-transparent text-[#2d2d2d] hover:text-[#2557a7] hover:border-[#2557a7]' }}">
        Browse categories
    </a>
</div>
