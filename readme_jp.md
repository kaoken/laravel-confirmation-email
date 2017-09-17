# laravel-confirmation-email
Laravelでユーザー仮登録後に確認メールを送り、指定アドレスにアクセス後に本登録が行われる。

[![TeamCity (simple build status)](https://img.shields.io/codeship/d6c1ddd0-16a3-0132-5f85-2e35c05e22b1.svg)]()
[![composer version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/kaoken/laravel-confirmation-email)
[![licence](https://img.shields.io/badge/licence-MIT-blue.svg)](https://github.com/kaoken/laravel-confirmation-email)
[![laravel version](https://img.shields.io/badge/Laravel%20version-≧5.5-red.svg)](https://github.com/kaoken/laravel-confirmation-email)


__コンテンツの一覧__

- [インストール](#インストール)
- [設定](#設定)
- [イベント](#イベント)
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
Authユーザーが`users`の場合(**必ず名前は、テーブル名にすること**)


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

### `.env`
* `CONFIRMATION_FROM_EMAIL` は、返信先のメールアドレス。デフォルトで、デフォルトのメールアドレスになる。
* `CONFIRMATION_FROM_NAME` は、返信先の名前。デフォルトで`webmaster`になる。


### メール
上記設定のコンフィグ`config\auth.php`の場合、
`email_confirmation`の`Kaoken\LaravelConfirmation\Mail\ConfirmationMailToUser`は、
仮登録時に確認メールとして使用する。テンプレートは、`views\vendor\confirmation\confirmation.blade.php`
を使用している。  
  
`email_registration`の`Kaoken\LaravelConfirmation\Mail\RegistrationMailToUser`は、
本登録をしたことを知らせるメールとして使用する。テンプレートは、`views\vendor\confirmation\registration.blade.php`
を使用している。




### コントローラー
仮登録、本登録、ログインの例
```php
<?php
namespace App\Http\Controllers;
use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Kaoken\LaravelConfirmation\Controllers\AuthenticatesUsers;
use Kaoken\LaravelConfirmation\Controllers\ConfirmationUser;

class RegisterUserController extends Controller
{
    use AuthenticatesUsers, ConfirmationUser;
    /**
     * Authユーザーモデルクラスを取得する
     * @return string
     */
    protected function getAuthUserClass() { return User::class; }
    /**
     * 仮登録画面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getFirstRegister()
    {
        // 各自で用意する
        return view('first_step_register');
    }
    
    /**
     * ユーザーの仮登録をする
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function postFirstRegister(Request $request)
    {
        $all = $request->only(['name', 'email', 'password']);
        $validator = Validator::make($all,[
            'name' => 'required|max:24',
            'email' => 'required|unique:users,email|max:255|email',
            'password' => 'required|between:6,32'
        ]);

        if ($validator->fails()) {
            return redirect('first_register')
                ->withErrors($validator)
                ->withInput();
        }
        $all['password'] = bcrypt($all['password']);

        // 仮登録をする
        if ( !$this->createUserAndSendConfirmationLink($all) ) {
            return redirect('first_register')
                            ->withErrors(['confirmation'=>'仮登録に失敗しました。']);
        }
        // 仮登録を知らせるページへ移動
        return redirect('first_register_ok');
    }
}
```
### ルート
上記コントローラより
```php
        Route::get('register', 'AuthController@getFirstRegister');
        Route::post('register', 'AuthController@postFirstRegister');
        Route::get('register/{email}/{token}', 'AuthController@getRegistration');
```

## イベント
`vendor\kaoken\laravel-confirmation-email\src\Events`ディレクトリ内を参照!  

#### `BeforeCreateUserEvent`
ユーザーが作成される前に呼び出されます。  
**注意**： このイベントが呼び出されると、Authユーザー作成に関連するDBトランザクションが進行中。  
リスナーで例外を作成すると、ターゲットのAuthユーザー作成が直ちにロールバックされる。  

#### `BeforeDeleteUsersEvent`
期限切れのユーザーを削除する前に呼び出される。  
これは、`Confirmation::broker('hoge')->deleteUserAndToken();`のメソッドの引数を`true`に`deleteUserAndToken(true)`した
場合のみ呼び出される。
**注意**： このイベントが呼び出されると、期限切れAuthユーザー削除に関連するDBトランザクションが進行中。  
リスナーで例外を作成すると、ターゲットのAuthユーザー削除が直ちにロールバックされる。  


#### `CreatedUserEvent`
Authユーザーが作成された後に呼び出されます。  

#### `ConfirmationEvent`
確認メールを送信した後、認証ユーザーが作成されて呼び出されます。  

#### `RegistrationEvent`
Authユーザーが本登録した後に呼び出されます。



## ライセンス

[MIT](https://github.com/kaoken/laravel-confirmation-email/blob/master/LICENSE.txt)