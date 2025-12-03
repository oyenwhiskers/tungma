# Tung Ma Express Management System

A comprehensive logistics and bill management system built with Laravel 11.

## Features

- Role-based access control (Super Admin, Admin, Staff)
- Company management with soft deletes
- Bill tracking with payment details, customer info, and SST
- Courier policy management linked to companies
- Analytics dashboard with revenue and staff distribution
- Storage metrics and clearing actions
- Profile management with password reset

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

## Built With Laravel

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

Laravel is a web application framework with expressive, elegant syntax. Visit [laravel.com](https://laravel.com) for documentation.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
