<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateAdminUserCommand extends Command
{
    protected $signature = 'admin:create-user
                            {email : Email đăng nhập admin}
                            {--name= : Tên hiển thị}
                            {--password= : Mật khẩu admin}';

    protected $description = 'Tạo hoặc cập nhật tài khoản quản trị cho khu admin';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        $name = trim((string) ($this->option('name') ?: 'Administrator'));
        $password = (string) ($this->option('password') ?: $this->secret('Nhập mật khẩu admin'));

        if ($password === '') {
            $this->error('Mật khẩu không được để trống.');

            return self::FAILURE;
        }

        if (mb_strlen($password) < 8) {
            $this->error('Mật khẩu admin phải có ít nhất 8 ký tự.');

            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->name = $name;
        $user->password = $password;
        $user->is_admin = true;
        $user->save();

        $this->info("Đã sẵn sàng tài khoản admin: {$user->email}");

        return self::SUCCESS;
    }
}