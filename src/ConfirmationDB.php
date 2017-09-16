<?php

namespace Kaoken\LaravelConfirmation;

use DB;
use Kaoken\LaravelConfirmation\Events\BeforeCreateUserEvent;
use Kaoken\LaravelConfirmation\Events\CreatedUserEvent;
use Kaoken\LaravelConfirmation\Events\RegistrationEvent;
use Log;
use \Exception;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\ConnectionInterface;

class ConfirmationDB
{
    /**
     * The database connection instance.
     *
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * The token database table name.
     *
     * @var string
     */
    protected $table;

    /**
     * Auth user class derived from Mode
     * Example: `\App\User::class`
     * @var string
     */
    protected $model;

    /**
     * The hashing key.
     *
     * @var string
     */
    protected $hashKey;

    /**
     * The number of seconds a token should last.
     *
     * @var int
     */
    protected $expires;

    /**
     * Create a new token repository instance.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @param  string  $table   The token database table name.
     * @param  string  $model   Auth user class derived from Mode.   {Model}::class
     * @param  string  $hashKey The Hash key
     * @param  int     $expires Unit is hour. Default 24 hours.
     */
    public function __construct(ConnectionInterface $connection, $table, $model, $hashKey, $expires = 24)
    {
        $this->table = $table;
        $this->model = $model;
        $this->hashKey = $hashKey;
        $this->expires = $expires;
        $this->connection = $connection;
    }

    /**
     * Create a new token and Auth user.
     *
     * @param  array $data
     * @return string
     */
    public function create(array $data)
    {
        $user = null;
        $this->connection->beginTransaction();
        try{
            event(new BeforeCreateUserEvent($data));
            $user = ($this->model)::where('email',$data['email'])
                    ->lockForUpdate()
                    ->first();
            if( !is_null($user))
                throw new Exception("A mail address that already exists.\n",422);
            $user = ($this->model)::create($data);
        }catch(Exception $e){
            $this->connection->rollback();
            if( $e->getCode() === 422 ){
                return IConfirmationBroker::EXISTS_USER;
            }else{
                Log::error("ConfirmationDB::create user create Fail!!\n",$e->getMessage());
            }
            return IConfirmationBroker::INVALID_USER;
        }

        try{
            $this->deleteExisting($user);

            $token = $this->createNewToken();

            if( !$this->getTable()->insert($this->getPayload($user->email, $token)) ){
                throw new Exception();
            }
        }catch(Exception $e){
            $this->connection->rollback();
            Log::error("ConfirmationDB::create token insert Fail!!\n",$e->getMessage());
            return IConfirmationBroker::INVALID_CONFIRMATION;
        }
        $this->connection->commit();
        event(new CreatedUserEvent($user));
        return $token;
    }

    /**
     * Acquire user data derived from "Model"
     * @param string $email
     * @return Model|null
     */
    public function getUser($email)
    {
        return ($this->model)::where('email',$email)->first();
    }

    /**
     * Is there a combination of the specified email address and token?
     * @param string $email mail address
     * @param string $token token
     * @return bool Returns true if it exists.
     */
    public function checkEMailToken($email, $token)
    {
        $val = $this->getTable()->where('email',$email)
            ->where('token', $token)
            ->count();
        return $val === 1;
    }

    /**
     * Delete all existing reset tokens from the database.
     *
     * @param  object $user Auth user model
     * @return int
     */
    protected function deleteExisting($user)
    {
        return $this->getTable()->where('email', $user->email)->delete();
    }

    /**
     * Build the record payload for the table.
     *
     * @param  string  $email
     * @param  string  $token
     * @return array
     */
    protected function getPayload($email, $token)
    {
        return ['email' => $email, 'token' => $token, 'created_at' => new Carbon];
    }

    /**
     * Determine if the token has expired.
     *
     * @param  array  $token
     * @return bool
     */
    protected function tokenExpired($token)
    {
        $expiresAt = Carbon::parse($token['created_at'])->addHour($this->expires);

        return $expiresAt->isPast();
    }

    /**
     * Delete the record of the token and perform Complete registration work.
     *
     * @param  string  $email
     * @param  string  $token
     * @return string
     */
    public function registration($email,$token)
    {
        // Auth user table name
        $userTbl = '';
        $user = null;

        try{
            $this->connection->beginTransaction();
            $user = (new $this->model)::where('email', '=', $email)->first();

            $userTbl = $user->getTable();

            if( !$this->checkEMailToken($email,$token) )
                throw new Exception(IConfirmationBroker::INVALID_TOKEN);

            // Auth user lock prevention
            $n = $this->connection->table($userTbl)
                ->lockForUpdate()
                ->where('email', '=', $email)
                ->count();

            // Already deleted?
            if( $n === 0 )
                throw new Exception(IConfirmationBroker::INVALID_USER);

            $ret = $this->getTable()
                ->lockForUpdate()
                ->where('email',$email)
                ->where('token', $token)
                ->delete();

            $this->connection->commit();
        }catch(Exception $e){
            $this->connection->rollback();
            $msg = '';
            $isError = false;
            switch ($e->getMessage()){
                case IConfirmationBroker::INVALID_TOKEN:
                    //$msg = "Already there is no token.";
                    break;
                case IConfirmationBroker::INVALID_USER:
                    $msg = 'Already delete a user[table:'.$userTbl.',email:'.$email.'].';
                    $isError = true;
                    break;
                default:
                    $isError = true;
                    $msg = $e->getMessage();
            }
            if( $isError ) Log::error("ConfirmationDB::delete Fail!!\n".$msg);
            return $e->getMessage();
        }
        event(new RegistrationEvent($user));
        return IConfirmationBroker::REGISTRATION;
    }

    /**
     * Delete Auth users and tokens that are pre-registered and have expired.
     * @note Let's add it to the kernel method with reference to the example below.
     *
     *  App\Console\Kernel::schedule(Schedule $schedule){
     *      $schedule->call(function(){
     *          Confirmation::broker('user')->deleteUserAndToken();
     *      )->hourly();
     *  }
     *
     * @return int Number of deleted users.
     */
    public function deleteUserAndToken()
    {
        $ret = 0;
        try{
            $this->connection->beginTransaction();
            $userTbl = with(new $this->model)->getTable();
            $expiredAt = Carbon::now()->subHour($this->expires);

            // Delete expired Auth users
            $ret=$this->connection->table($userTbl)
                ->lockForUpdate()
                ->join($this->table, $this->table.'.email', '=', $userTbl.'.email')
                ->where($this->table.'.created_at', '<', $expiredAt)
                ->delete();

            // Delete expired tokens
            $this->getTable()
                ->lockForUpdate()
                ->where('created_at', '<', $expiredAt)
                ->delete();
            $this->connection->commit();
        }catch(Exception $e){
            $this->connection->rollback();
            Log::error("ConfirmationDB::deleteUserAndToken Fail!!\n".$e->getMessage());
        }
        return $ret;
    }

    /**
     * Is there a mail address?
     * @param string $email
     * @return bool Returns true if it exists.
     */
    public function existenceEmail($email)
    {
        return $this->getTable()
            ->where('email', '=', $email)
            ->count() > 0;
    }

    /**
     * Create a new token for the user.
     *
     * @return string
     */
    public function createNewToken()
    {
        return hash_hmac('sha256', Str::random(40), $this->hashKey);
    }

    /**
     * Begin a new database query against the table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getTable()
    {
        return $this->connection->table($this->table);
    }

    /**
     * Get the database connection instance.
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }
}
