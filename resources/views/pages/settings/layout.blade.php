<div class="casino-settings">
    <div class="casino-settings__hero">
        <div>
            <p class="casino-eyebrow">CONTA ALLINBET</p>
            <flux:heading size="xl" level="1">Definições</flux:heading>
            <flux:subheading size="lg">Gere o teu perfil, segurança e preferências da conta.</flux:subheading>
        </div>
        <a href="{{ route('dashboard') }}" wire:navigate class="casino-settings__back">
            ← Voltar ao casino
        </a>
    </div>

    <div class="casino-settings__grid">
        <aside class="casino-settings__nav" aria-label="Definições da conta">
            <p class="casino-settings__nav-title">CONTA</p>
            <flux:navlist>
                <flux:navlist.item :href="route('profile.edit')" wire:navigate icon="user">Perfil</flux:navlist.item>
                <flux:navlist.item :href="route('security.edit')" wire:navigate icon="lock-closed">Segurança</flux:navlist.item>
                <flux:navlist.item :href="route('appearance.edit')" wire:navigate icon="sun">Aparência</flux:navlist.item>
            </flux:navlist>

            <div class="casino-settings__tip">
                <span aria-hidden="true">✦</span>
                <div>
                    <strong>Conta protegida</strong>
                    <p>Usa uma palavra-passe forte e autenticação adicional quando disponível.</p>
                </div>
            </div>
        </aside>

        <main class="casino-settings__content">
            <div class="casino-settings__section-head">
                <div>
                    <p class="casino-eyebrow">{{ $heading ?? '' }}</p>
                    <flux:heading size="lg">{{ $heading ?? '' }}</flux:heading>
                    <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>
                </div>
            </div>
            <div class="casino-settings__panel">
                {{ $slot }}
            </div>
        </main>
    </div>
</div>