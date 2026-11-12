<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

# Commit messages

The owner wants every change committed with a tidy, structured message, written
for them. Follow this for every commit in this repository:

- Format: Conventional Commits, `<type>(<scope>): <summary>`. Type and scope in
  English keywords; summary and body in Indonesian.
- Types: feat, fix, refactor, test, docs, style, perf, build, chore.
- Scopes are the app's modules: brand, koleksi, fase, produk, kain, vendor,
  harga, kas, budget, pembayaran, penjualan, stok, promosi, dashboard,
  pengaturan, auth, pwa, ui, db. Omit the scope for changes across many.
- Summary: imperative ("tambah", "perbaiki", "ubah"), 72 characters at most, no
  trailing period.
- Body: a blank line, then why the change was needed and what changed for the
  user, as short bullets. Skip the body for a one-line change.
- One logical change per commit. Unrelated changes go in separate commits.
- Commit when a piece of work is finished and the tests pass. Push only when
  the owner asks.

The template the owner sees for manual commits is `.gitmessage`
(`git config commit.template .gitmessage`).
