# laravel-confirmation-email
Laravelでユーザー仮登録後に確認メールを送り、指定アドレスにアクセス後に本登録が行われる。

[![TeamCity (simple build status)](https://img.shields.io/codeship/d6c1ddd0-16a3-0132-5f85-2e35c05e22b1.svg)]()
[![composer version](https://img.shields.io/badge/version-0.0.0-blue.svg)](https://github.com/kaoken/laravel-confirmation-email)
[![licence](https://img.shields.io/badge/licence-MIT-blue.svg)](https://github.com/kaoken/laravel-confirmation-email)
[![laravel version](https://img.shields.io/badge/Laravel%20version-≧5.5-red.svg)](https://github.com/kaoken/laravel-confirmation-email)


__コンテンツの一覧__

- [インストール](#インストール)
- [設定](#設定)
- [ライセンス](#ライセンス)

## インストール

**composer**:

```bash
composer install kaoken/laravel-confirmation-email
```

または、`composer.json`へ追加
```json 
  "require": {
    ...
    "kaoken/laravel-confirmation-email":"^1.0"
  }
```

## 設定

### **`config\app.php` に以下のように追加：**
``` config\app.php
    'providers' => [
        ...
        // 追加
        Kaoken\LaravelConfirmation\ConfirmationServiceProvider::class
    ],

    'aliases' => [
        ...
        // 追加
        'Confirmation' => Kaoken\Laravel\Facades\Confirmation::class
    ],
```

### config\auth.phpへ追加する例
Authユーザーが`users`の場合


- `model`は、ユーザーモデルクラス
- `path`は、トークを使用した登録時に使用するURLの途中パス(例：`http(s):://hoge.com/{path}/{token}`)
- `provider`は、ユーザーのテーブル名
- `email_confirmation`は、[Mailable](https://readouble.com/laravel/5.5/ja/mail)で派生したクラスを必要に応じて変更すること。
確認メールを送るときに使用する。
- `email_registration`は、[Mailable](https://readouble.com/laravel/5.5/ja/mail)で派生したクラスを必要に応じて変更すること。
登録が終了したときに送るメール。
- `table`は、このサービスで使用するテーブル名
- `expire`は、登録後にX時間操作しない場合、仮登録したユーザーが削除される時間

``` config\auth.php
    'confirmations' => [
        'users' => [
            'model' => App\User::class,
            'path' => 'user/register/',
            'email_confirmation' => Kaoken\LaravelConfirmation\Mail\ConfirmationMailToUser::class,
            'email_registration' => Kaoken\LaravelConfirmation\Mail\RegistrationMailToUser::class,
            'table' => 'confirmation_users',
            'expire' => 24,
        ]
    ],
```

### コマンドの実行
```bash
php artisan vendor:publish --tag=confirmation
```
実行後、以下のディレクトリやファイルが追加される。   

* **`database`**
  * **`migrations`**
    * `2017_09_14_000001_create_confirmation_users_table.php`
* **`resources`**
  * **`lang`**
    * **`en`**
      * `confirmation.php`
    * **`ja`**
      * `confirmation.php`
  * **`views`**
    * **`vendor`**
      * **`confirmation`**
        * **`mail`**
          * **`text`**
            * `confirmation.blade.php`
            * `registration.blade.php`
  * `registration.blade.php`
     
### マイグレーション
マイグレーションファイル`2017_09_14_000001_create_confirmation_users_table.php`は、必要に応じて
追加修正すること。

```bash
php artisan migrate
```

### カーネルへ追加
`app\Console\Kernel.php`の`schedule`メソッドへ追加する。  
これは、仮登録後24時間過ぎたユーザーを削除するために使用する。
```php
    protected function schedule(Schedule $schedule)
    {
        ...
        App\Console\Kernel::schedule(Schedule $schedule){
            $schedule->call(function(){
                Confirmation::broker('user')->deleteUserAndToken();
            )->hourly();
        }
    }
```

### コントローラー
```php

```


## ライセンス

[MIT](https://github.com/markdown-it/markdown-it/blob/master/LICENSE)