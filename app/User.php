<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes;

    protected $appends = ['create_data', 'update_data'];
    protected $primaryKey = 'id_User';
    public $timestamps = false;
    protected $acessos_negados_mod;
    protected $grupoUser;
    protected $dates = ['deleted_at'];


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id_User',
        'user_Name',
        'user_Login',
        'user_Email',
        'password',
        'dt_Create',
        'dt_Update',
        'id_User_Update',
        'id_User_Create',
        'id_User_Updade_Status',
        'user_CPF',
        'ativo',
        'id_tipoUsuario',
        'id_grupo',
        'id_monitor',
        'remember_token'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'dt_Create', 'dt_Update'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // protected $with = ['Carteiras'];

    public function getCreateDataAttribute()
    {
        return date('d-m-Y h:i:s', strtotime($this->attributes['dt_Create']));
    }

    public function getUpdateDataAttribute()
    {
        return date('d-m-Y h:i:s', strtotime($this->attributes['dt_Update']));
    }

    public function Carteiras(){
        return $this->hasMany('App\TbCarteira', 'id_User_Create');
    }

    public function getUsersCriador(){
        return $this->belongsTo('App\User', 'id_User_Create');
    }

    public function getUsersAtualizador(){
        return $this->belongsTo('App\User', 'id_User_Update');
    }

    public function getUsersUpdateStatus(){
        return $this->belongsTo('App\User', 'id_User_Updade_Status');
    }

    public function getUsersMonitor(){
        return $this->belongsTo('App\User', 'id_monitor');
    }

    public function getGroups(){
        return $this->belongsTo('App\TbGroups', 'id_grupo');
    }

    public function getAcesso(){
        return $this->hasMany('App\TbAccessmod', 'id_from');
    }

    public function getAcessoSiteUser(){
        return $this->hasMany('App\TbAccessitemmod', 'id_from')->where([
            ['tb_accessitemmods.flag_grupo', '=', NULL],
            ['tb_accessitemmods.revogar', '=', NULL]
        ]);
    }

    protected function getUserGroup(){
        return $this->hasMany('App\TbAccessGroups', 'id_user')->select(['id_grupo']);
    }

    public function getModuloUser()
    {
        $strSQLTempClear = "DROP TABLE IF EXISTS acessos_negados_mod";
        DB::statement($strSQLTempClear);

        $strSQLTemp = 'CREATE TEMPORARY TABLE acessos_negados_mod
                        SELECT
                            tb_accessmods.id_modulo as modulo
                        FROM
                            tb_modulos
                        INNER JOIN
                            tb_accessmods
                        ON
                            tb_modulos.id_modulo = tb_accessmods.id_modulo
                        WHERE
                            tb_accessmods.id_from = ' . $this->id_User . '
                        AND
                            tb_accessmods.flag_grupo is NULL
                        AND
                            tb_accessmods.revogar = 1';
        DB::statement($strSQLTemp);

        $objResult = DB::table('acessos_negados_mod')->get();
        $this->acessos_negados_mod = json_decode(json_encode($objResult), true);

        $this->grupoUser = $this->getUserGroup;

        $retorno = $this->hasManyThrough(
            'App\TbModulo',
            'App\TbAccessmod',
            'id_from',
            'id_modulo',
            'id_User',
            'id_modulo'
        )
        ->select([DB::raw($this->id_User . ' as id_from'), 'tb_modulos.id_modulo', 'tb_modulos.nomeModulo', 'tb_modulos.urlModulo', 'tb_modulos.ativo'])
        ->groupBy('tb_modulos.id_modulo', 'tb_modulos.nomeModulo', 'tb_modulos.urlModulo', 'tb_modulos.ativo')
        ->whereNull(['tb_accessmods.revogar', 'tb_accessmods.flag_grupo'])
        ->orWhere(function ($query) {
            $query->where(function ($query1) {
                $query1->whereIn('tb_accessmods.id_from', $this->grupoUser)
                    ->where('tb_accessmods.flag_grupo', '=', 1)
                    ->whereNull('tb_accessmods.revogar');
            });
            $query->where(function($query2){
                $query2->whereNotIn('tb_modulos.id_modulo', $this->acessos_negados_mod);
            });
        });

        return $retorno;
    }

    public function getModuloGroup()
    {
        return $this->hasManyThrough(
            'App\TbModulo',
            'App\TbAccessmod',
            'id_from',
            'id_modulo',
            'id_grupo',
            'id_modulo'
        )
        ->select(['id_modulo', 'id_from'])
        ->where('tb_accessmods.flag_grupo', 1);
    }

    public function getGruposLigados(){
        return $this->hasManyThrough(
            'App\TbGroups',
            'App\TbAccessGroups',
            'id_user',
            'id',
            'id_User',
            'id_grupo'
        )->select(['tb_groups.id', 'tb_groups.nome_grupo', 'tb_groups.desc_grupo']);
    }

    public function logsDoUsuario($id){
        if (Storage::disk('public')->exists('Log_GERAL/User/Log_' . $id .'.log')) {
            $arr = [];
            $pathLog = 'Log_GERAL/User/Log_' . $id .'.log';
            $arquivo = fopen(storage_path('app/public/' . $pathLog), 'r');

            $count = 0;
            $number = isset($_GET['countLog'])? $_GET['countLog'] : 1;
            $fim = ($number * 15);
            $ini = $fim - 15;

            while($linha = fgets($arquivo, 1024)) {
                $count++;
                if ($count > $ini && $count <= $fim) {
                    $linha = preg_replace('/\r\n/', '', $linha);
                    $keys = array('DataLog', 'Status', 'Mensagem', 'Model', 'Controller', 'IDUser', 'Name', 'Request', 'Type');
                    $linha = array_combine($keys, explode("|",$linha));
                    if ($linha['Request']) {
                        $div = explode('%', $linha['Request']);
                        $linha['Request'] = array_combine(explode("&",$div[0]), explode("&",$div[1]));
                    }
                    array_push($arr, $linha);
                }
            }
            fclose($arquivo);

            $pages = ceil($count / 15);

            return [
                'tarefas'   =>  $arr,
                'first'     =>  ($pages > 0) ? env('APP_URL') . '/usuario/' . $id . '/?arr=log&countLog=1'          :   NULL,
                'last'      =>  ($pages > 0) ? env('APP_URL') . '/usuario/' . $id . '/?arr=log&countLog=' . $pages  :   NULL,
                'pageNext'  =>  (($number + 1) > $pages) ?  NULL    :   env('APP_URL') . '/usuario/' . $id . '/?arr=log&countLog=' . ($number + 1),
                'pagePrev'  =>  (($number - 1) == 0) ?  NULL        :   env('APP_URL') . '/usuario/' . $id . '/?arr=log&countLog=' . ($number - 1)
            ];
        }else{
            return [];
        }
    }
}
