<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\User;

use Cache;
use Lang;
use Gate;
use Auth;

class HomeController extends Controller
{
	const USER_LIMIT = 100;
	public function index(){
		$recent_problemsets = Cache::tags(['wzoj'])->remember('recent_problemsets', 1, function(){
			return \App\Problemset::where('type', '=', 'set')->orderBy('updated_at', 'desc')->take(6)->get();
		});
		$recent_contests = Cache::tags(['wzoj'])->remember('recent_contests', 1, function(){
			return \App\Problemset::where('type','<>', 'set')->orderBy('contest_start_at', 'desc')->take(6)->get();
		});
		$home_page_problemsets=[];
		foreach($recent_problemsets as $problemset){
			if($problemset->public || Gate::allows('view',$problemset)){
				array_push($home_page_problemsets,$problemset);
			}
		}

		$top_users = Cache::tags(['wzoj'])->remember('top_users', 1, function(){
			return User::orderBy('cnt_ac', 'desc')->take(10)->withoutAdmin()->get();
		});

		return view('home',[
			'home_page_problemsets' => $home_page_problemsets,
			'recent_contests' => $recent_contests,
			'top_users' => $top_users]);
	}
	public function faq(){
		return view('faq.'.Lang::locale());
	}

	public function ranklist(Request $request){
		$this->validate($request, ['page' => 'integer']);
		$page = 1;
		if(isset($request->page)) $page = $request->page;
		$users = User::orderBy('cnt_ac', 'desc')
				->skip(($page - 1) * self::USER_LIMIT)
				->take(self::USER_LIMIT)
				->withoutAdmin()
				->get();
		return view('ranklist', [
				'users' => $users,
				'start_rank' => ($page-1) * self::USER_LIMIT,
				'cur_page' => $page,
				'max_page' => (User::count()-1) / self::USER_LIMIT + 1]);
	}

	public function getSorry(Request $request){
		if((!Auth::check()) || $request->user()->bot_tendency < 100){
			return redirect('/');
		}
		\Session::put('url.intended', \URL::previous());
		return view('sorry');
	}

	public function postSorry(Request $request){
		if(!(Auth::check())) return redirect('/');
		$this->validate($request,[
			'captcha' => 'required|captcha']);
		\App\User::where('id', $request->user()->id)
			->update(['bot_tendency' => 0]);
		return redirect()->intended('/');
	}
}
