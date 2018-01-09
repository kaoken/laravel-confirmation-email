# laravel-confirmation-email
Laravel sends confirmation mail after Auth user first registration, complete registration is done after accessing designated address.

[![Travis](https://img.shields.io/travis/rust-lang/rust.svg)]()
[![composer version](https://img.shields.io/badge/version-1.1.2-blue.svg)](https://github.com/kaoken/laravel-confirmation-email)
[![licence](https://img.shields.io/badge/licence-MIT-blue.svg)](https://github.com/kaoken/laravel-confirmation-email)
[![laravel version](https://img.shields.io/badge/Laravel%20version-≧5.5-red.svg)](https://github.com/kaoken/laravel-confirmation-email)

__Table of content__

- [Install](#install)
- [Setting](#setting)
- [Event](#event)
- [License](#license)

## Install

**composer**:

```bash
composer require kaoken/laravel-confirmation-email
```

or, add `composer.json`  

```json 
  "require": {
    ...
    "kaoken/laravel-confirmation-email":"^1.1"
  }
```



## Setting

### Add to **`config\app.php`** as follows:
``` config\app.php
    'providers' => [
        ...
        // add
        Kaoken\LaravelConfirmation\ConfirmationServiceProvider::class
    ],

    'aliases' => [
        ...
        // add
        'Confirmation' => Kaoken\LaravelConfirmation\Facades\Confirmation::class
    ],
```
  
or, add `composer.json`  
  
```js
{
    ...
    "extra": {
        "laravel": {
            "dont-discover": [
            ],
            "providers": [
                "Kaoken\\LaravelConfirmation\\ConfirmationServiceProvider",
            ],
            "aliases": {
                "MailReset": "Kaoken\\LaravelConfirmation\\Facades\\Confirmation"
            }
        }
    },
    ...
}
```
  
### Example of adding to **`config\auth.php`**

add `'confirmation' => 'users',`.
```php
[
    ...
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
        // add
        'confirmation' => 'users',
    ],
    ...
]
```  

When the Auth user is `users`(**Make sure Auth user name is table name!**)


- `model` is a user model class
- `provider` is the user table name
- `email_confirmation` should modify the class derived from[Mailable](https://laravel.com/docs/5.5/mail) as necessary.
Used to send confirmation mail.
- `email_registration` should modify the class derived from[Mailable](https://laravel.com/docs/5.5/mail) as necessary.
Mail sent when complete registering.
- `table` is the name of the table used for this service
- If `expire` does not manipulate X hours after registration, the 1st registered user is deleted.

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

### Command
```bash
php artisan vendor:publish --tag=confirmation
```
After execution, the following directories and files are added.

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
     
### Migration
Migration file `2017_09_14_000001_create_confirmation_users_table.php` should be modified as necessary.


```bash
php artisan migrate
```

### Add to kernel
Add it to the `schedule` method of `app\Console\Kernel.php`.  
This is used to delete users who passed 24 hours after 1st registration.
```php
    protected function schedule(Schedule $schedule)
    {
        ...
        $schedule->call(function(){
            Confirmation::broker('user')->deleteUserAndToken();
        )->hourly();
    }
```


### E-Mail
In the configuration `config\auth.php` with the above setting,
`Kaoken\LaravelConfirmation\Mail\ConfirmationMailToUser` of `email_confirmation` 
is used as a confirmation mail at the time of 1st registration. 
The template uses `views\vendor\confirmation\confirmation.blade.php`.
 
`Kaoken\LaravelConfirmation\Mail\RegistrationMailToUser` of `email_registration` 
is used as a mail informing that the　complete registration was done. 
The template uses `views\vendor\confirmation\registration.blade.php`.
Change according to the specifications of the application.


### controller
Example of **1st registration**, **complete registration**, **login**
```php
<?php
namespace App\Http\Controllers;
use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Kaoken\LaravelConfirmation\Controllers\AuthenticatesUsers;
use Kaoken\LaravelConfirmation\Controllers\ConfirmationUser;

class RegisterUserController extends Controller
{
    use AuthenticatesUsers, ConfirmationUser;

    /**
     * Use with AuthenticatesUsers trait.
     * @var string
     */
    protected $broker = 'users';

    /**
     * 1st registration View
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getFirstRegister()
    {
        // Be prepared by yourself.
        return view('first_step_register');
    }
    
    /**
     * 1st registration
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

        if ( !$this->createUserAndSendConfirmationLink($all) ) {
            return redirect('first_register')
                            ->withErrors(['confirmation'=>'仮登録に失敗しました。']);
        }
        // Move to the page notifying 1st registration
        return redirect('first_register_ok');
    }
}
```
Be sure to add `$broker`.


### Route
From the above controller!
```php
Route::group([
    'middleware' => ['guest:user'] ],
    function(){
        Route::get('login', 'AuthController@login');
    }
);
Route::get('register', 'AuthController@getFirstRegister');
Route::post('register', 'AuthController@postFirstRegister');
Route::get('register/{email}/{token}', 'AuthController@getCompleteRegistration');
```
### Auth Model
Auth user model example
Added of `Kaoken\LaravelConfirmation\HasConfirmation;`
```php
<?php

namespace App;
use Kaoken\LaravelConfirmation\HasConfirmation;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable, HasConfirmation;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

}
```
By using the `confirmed()` method, we decide whether it is a registered user.  
When using social login, it is good to incorporate `confirmed()` so that it can be determined.

## Events
See inside the `vendor\kaoken\laravel-confirmation-email\src\Events` directory!

#### `BeforeCreateUserEvent`
Called before the user is created.  
**Warning**： When this event is invoked, a DB transaction related to Auth user creation is in progress.  
If you create an exception in the listener, the Auth user creation of the target is immediately rolled back.  
  
#### `BeforeDeleteUsersEvent`
Called before deleting expired users.  
This is only called if you `deleteUserAndToken(true)` the method argument to `true` in `Confirmation::broker('hoge')->deleteUserAndToken();`.

**Warning**： When this event is called, the DB transaction associated with expired Auth user deletion is in progress.  
If you create an exception with the listener, the target Auth user deletion is immediately rolled back.  


#### `CreatedUserEvent`
Called after Auth user is created.

#### `ConfirmationEvent`
After sending the confirmation mail,
An Auth user is created and called.

#### `RegistrationEvent`
Called after Auth user complete registration.



## License

[MIT](https://github.com/kaoken/laravel-confirmation-email/blob/master/LICENSE.txt)