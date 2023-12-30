# ZachW.site

![GitHub Package.json Version](https://img.shields.io/github/package-json/v/zachwatkins/zachw-site)
![Code Size](https://img.shields.io/github/languages/code-size/zachwatkins/zachw-site)
![License](https://img.shields.io/github/license/zachwatkins/zachw-site)
![Open Issues](https://img.shields.io/github/issues-raw/zachwatkins/zachw-site)

> IMPORTANT: This is work in progress! The project is subject to significant change until its 1.0.0 release.

## Status

![Laravel Pint](https://img.shields.io/github/actions/workflow/status/zachwatkins/zachw-site/lint-php.yml?label=Laravel%20Pint)
![Prettier](https://img.shields.io/github/actions/workflow/status/zachwatkins/zachw-site/lint-prettier.yml?label=Prettier)
![Pest Tests](https://img.shields.io/github/actions/workflow/status/zachwatkins/zachw-site/test-php.yml?label=Pest%20Tests)
![Release](https://img.shields.io/github/actions/workflow/status/zachwatkins/zachw-site/prerelease.yml?label=Release)
![Deployment](https://img.shields.io/github/actions/workflow/status/zachwatkins/zachw-site/release.yml?label=Deployment)

## Progress

![Closed Issues](https://img.shields.io/github/issues-closed/zachwatkins/zachw-site)
![Closed Pull Requests](https://img.shields.io/github/issues-pr-closed/zachwatkins/zachw-site)

## Issues

![Open Issues](https://img.shields.io/github/issues-raw/zachwatkins/zachw-site)
![Open Bug Issues](https://img.shields.io/github/issues/zachwatkins/zachw-site/bug)
![Open Feature Issues](https://img.shields.io/github/issues/zachwatkins/zachw-site/feature)
![Open Pull Requests](https://img.shields.io/github/issues-pr-raw/zachwatkins/zachw-site)
![Last Commit](https://img.shields.io/github/last-commit/zachwatkins/zachw-site)

- [Local System Requirements](#system-requirements)
- [Installation](#installation)
- [Features](#features)
- [Routes](#routes)
- [Development](#development)

## Local System Requirements

1. Node.js and NPM.
   - [Node.js](https://nodejs.org/en/download/)  
   - [NPM](https://www.npmjs.com/get-npm)
2. PHP and Composer: `$ bin/install-php`
   - [PHP](https://www.php.net/downloads.php)  
   - [Composer](https://getcomposer.org/download/)  
3. (Windows) Windows Subsystem for Linux and Ubuntu: `$ bin/install-wsl`
   - [Windows Subsystem for Linux](https://learn.microsoft.com/en-us/windows/wsl/install)  
   - [Ubuntu](https://www.microsoft.com/en-us/p/ubuntu/9nblggh4msv6?activetab=pivot:overviewtab)  

## Installation

1. Clone the repository: `$ git clone https://github.com/zachwatkins/zachw-site`
2. Open the project directory: `$ cd zachw-site`
3. Run the repository initialization script: `$ bin/once`

## Features

Laravel first-party packages and features:

1. __Breeze (Laravel Package)__ for user registration, login, authentication, and profile management. Also includes PHPUnit tests for authentication features.
2. __Sanctum (Laravel Package)__ for API authentication and token management.
4. __Queues__ for performing tasks asynchronously.
5. __Migrations__ for database schema management.
6. __Tests__ for peace of mind.

My own features:

1. __User Storage Scope__ shows how to store each user's files in their own folder.
2. __Generic Model Class__ demonstrates how Laravel registers, creates, and retrieves database table records.

## Routes

- [Public Web Routes](#public-web-routes)
- [Guest Web Routes](#guest-web-routes)
- [Authenticated Web Routes](#authenticated-web-routes)

### Public Web Routes

| Verb | URI | Action | Route Name |
| ---- | --- | ------ | ---------- |
| GET  | `/` | view   | welcome    |

### Guest Web Routes

| Verb      | URI                | Action  | Route Name |
| --------- | ------------------ | ------- | ---------- |
| GET       | `/register`        | create  | register   |
| POST      | `/register`        | store   | -          |
| GET       | `/login`           | create  | login      |
| POST      | `/login`           | store   | -          |
| GET       | `/forgot-password` | create  | password.request |
| POST      | `/forgot-password` | store   | password.email   |
| GET       | `/reset-password`  | create  | password.reset   |
| POST      | `/reset-password`  | store   | password.update  |

### Authenticated Web Routes

| Verb      | URI                          | Action  | Route Name |
| --------- | ---------------------------- | ------- | ---------- |
| GET       | `/verify-email`              | create  | verification.notice |
| GET       | `/verify-email/{id}/{hash}`  | create  | verification.verify |
| POST      | `/verify-email/{id}/{hash}`  | store   | - |
| POST      | `/verify-email-notification` | store   | verification.send |
| GET       | `/confirm-password`          | create  | password.confirm |
| POST      | `/confirm-password`          | store   | - |
| PUT       | `/password`                  | update  | password.update |
| POST      | `/logout`                    | destroy | logout |
| GET       | `/dashboard/`                | view    | dashboard |
| GET       | `/profile`                   | view    | profile.edit |
| PATCH     | `/profile`                   | update  | profile.update |
| DELETE    | `/profile`                   | destroy | profile.destroy |

### Authenticated API Routes

| Verb      | URI                 | Action  | Route Name |
| --------- | ------------------- | ------- | ---------- |
| GET       | `/api/user`         | closure | - |
| GET       | `/models`           | index   | photos.index |
| GET       | `/models/create`    | create  | photos.create |
| POST      | `/models`           | store   | photos.store |
| GET       | `/models/{id}`      | show    | photos.show |
| GET       | `/models/{id}/edit` | edit    | photos.edit |
| PUT/PATCH | `/models/{id}`      | update  | photos.update |
| DELETE    | `/models/{id}`      | destroy | photos.destroy

## Development

To start the application, you will need two terminal windows open. In the first window, run the following command:

```bash
php artisan serve
```

In the second window, run the following command:

```bash
npm run serve
```

If you are using Windows and want to run the application locally, you will need to install [Windows Subsystem for Linux](https://docs.microsoft.com/en-us/windows/wsl/install-win10).

### URLs

`http://localhost:80` (Web)
`http://localhost:8025` (Mailpit)

## Documentation

(Laravel Documentation)[./docs/laravel.md]
