<?php

namespace App\Http\Controllers;

use App\Teste;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\User;

class TesteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!session('foo')) {
            return 'Não existe session';
        }

        // $user = User::paginate(24);
        // return 'Verdade!';
        // return $user;
        // session()->put('foo', 'bar');

        $page = \Request::get('page');
        $experation = 60;
        $key = 'user_' . (empty($page)? 1 : $page);
        $total = 10;
        // return $key;

        // Para acessar a variavel $total dentro do Cache::remember temos que passar o use ($total) para conseguir
        return Cache::remember($key, $experation, function() use ($total){
            User::paginate($total);
            return session('foo');
            // return Cache::store('file')->get('bar', 'testando');
            // return 'Verdade!';
        });

        return Cache::remember($key, $experation, function() {
            return User::paginate(24);
        });

        // session()->put('foo', 'bar');
        // Cache::store('redis')->put('bar', 'baz', 20);
        // return Cache::store('redis')->get('bar');
        return Cache::get('bar', 'Testando');

        // return session('foo');
        // return session()->all();
        // return "Deu certo!";
    }

    private function user(){
        // Cache::store('redis')->put('bar', 'user', 20);
        // return Cache::get('bar');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Teste  $teste
     * @return \Illuminate\Http\Response
     */
    public function show(Teste $teste)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Teste  $teste
     * @return \Illuminate\Http\Response
     */
    public function edit(Teste $teste)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Teste  $teste
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Teste $teste)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Teste  $teste
     * @return \Illuminate\Http\Response
     */
    public function destroy(Teste $teste)
    {
        //
    }
}
