{{__('confirmation.msg_first_registration')}}

{{__('confirmation.msg_move_url')}}
{{ secure_url('user/register/'.$user->email.'/'.$token.'/') }}

・{{__('confirmation.msg_move_url')}}
・{{__('confirmation.msg_user_delete')}}


※ {{__('confirmation.msg_note')}}

──────────────────────────────────　
　Hoge
──────────────────────────────────　
web  : {{url('/')}}
email: hoge@hoge.com
──────────────────────────────────