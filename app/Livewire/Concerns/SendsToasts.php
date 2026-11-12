<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

/**
 * Short confirmations shown after an action, without interrupting the user.
 */
trait SendsToasts
{
    protected function toastSuccess(string $message): void
    {
        $this->toast($message, 'success');
    }

    protected function toastError(string $message): void
    {
        $this->toast($message, 'danger');
    }

    protected function toastInfo(string $message): void
    {
        $this->toast($message, 'info');
    }

    protected function toastWarning(string $message): void
    {
        $this->toast($message, 'warning');
    }

    private function toast(string $message, string $tone): void
    {
        $this->dispatch('notify', message: $message, tone: $tone);
    }

    /*
    | The same confirmation, but for a save that ends in a page load.
    |
    | A toast is a browser event, and a browser event dies with the page that
    | was going to receive it. Anything that changes what the shell around the
    | component draws — the name in the sidebar, whether the Asisten entry
    | exists, the language everything is written in — has to reload the page
    | to show it, and the confirmation has to survive that trip. So it goes
    | into the session instead, and `x-toasts` picks it up on the way in.
    */

    protected function flashSuccess(string $message): void
    {
        $this->flashToast($message, 'success');
    }

    protected function flashInfo(string $message): void
    {
        $this->flashToast($message, 'info');
    }

    protected function flashWarning(string $message): void
    {
        $this->flashToast($message, 'warning');
    }

    private function flashToast(string $message, string $tone): void
    {
        session()->flash('toast', ['message' => $message, 'tone' => $tone]);
    }

    /**
     * Redraw the whole page, shell included, and land back where we are.
     *
     * `navigate: true` makes it a fetch and a swap rather than a browser
     * reload, so it costs a request and keeps the scroll position instead of
     * flashing white.
     */
    protected function refreshShell(string $route): void
    {
        $this->redirectRoute($route, navigate: true);
    }
}
