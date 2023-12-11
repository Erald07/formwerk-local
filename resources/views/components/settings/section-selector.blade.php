<!-- Sections -->
<div class="flex px-3 pb-1.5 md:pb-0 mb-3 md:mb-0 md:flex-col overflow-x-scroll md:overflow-x-hidden">
    <a
        href="#general"
        class="py-1 md:py-3 md:pr-3 mr-3 md:mr-0 whitespace-nowrap md:whitespace-normal"
        :class="{ 'border-b-2 border-r-0 md:border-b-0 md:border-r-2 border-gray-300': section === 'general' }"
        @click="section = 'general'"
    >
        {{ __('General') }}
    </a>
    <a
        href="#smtp"
        class="py-1 md:py-3 md:pr-3 mr-3 md:mr-0 whitespace-nowrap md:whitespace-normal"
        :class="{ 'border-b-2 border-r-0 md:border-b-0 md:border-r-2 border-gray-300': section === 'smtp' }"
        @click="section = 'smtp'"
    >
        {{ __('SMTP') }}
    </a>
    <a
        href="#sftp"
        class="py-1 md:py-3 md:pr-3 mr-3 md:mr-0 whitespace-nowrap md:whitespace-normal"
        :class="{ 'border-b-2 border-r-0 md:border-b-0 md:border-r-2 border-gray-300': section === 'sftp' }"
        @click="section = 'sftp'"
    >
        {{ __('SFTP') }}
    </a>
    <a
        href="#sms"
        class="py-1 md:py-3 md:pr-3 mr-3 md:mr-0 whitespace-nowrap md:whitespace-normal"
        :class="{ 'border-b-2 border-r-0 md:border-b-0 md:border-r-2 border-gray-300': section === 'sms' }"
        @click="section = 'sms'"
    >
        {{ __('SMS') }}
    </a>
    <a
        href="#user-management"
        class="py-1 md:py-3 md:pr-3 mr-3 md:mr-0 whitespace-nowrap md:whitespace-normal"
        :class="{ 'border-b-2 border-r-0 md:border-b-0 md:border-r-2 border-gray-300': section === 'user-management' }"
        @click="section = 'user-management'"
    >
        {{ __('User management') }}
    </a>
    <a
        href="#api"
        class="py-1 md:py-3 md:pr-3 mr-3 md:mr-0 whitespace-nowrap md:whitespace-normal"
        :class="{ 'border-b-2 border-r-0 md:border-b-0 md:border-r-2 border-gray-300': section === 'api' }"
        @click="section = 'api'"
    >
        {{ __('API credentials') }}
    </a>
    <a
        href="#change-password"
        class="py-1 md:py-3 md:pr-3 mr-3 md:mr-0 whitespace-nowrap md:whitespace-normal"
        :class="{ 'border-b-2 border-r-0 md:border-b-0 md:border-r-2 border-gray-300': section === 'change-password' }"
        @click="section = 'change-password'"
    >
        {{ __('Change password') }}
    </a>
    <a
        href="#predefined-values"
        class="py-1 md:py-3 md:pr-3 mr-3 md:mr-0 whitespace-nowrap md:whitespace-normal"
        :class="{ 'border-b-2 border-r-0 md:border-b-0 md:border-r-2 border-gray-300': section === 'predefined-values' }"
        @click="section = 'predefined-values'"
    >
        {{ __('Moodle config') }}
    </a>
</div>

