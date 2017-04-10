<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Cache;
use App\Problemset;
use DB;

use App\Solution;
use App\Problem;
use App\Testcase;

class JudgerController extends Controller
{
	public function __construct(){
		$this->middleware('judger');
	}
	public function getIndex(){
		return response()->json(['ok' => true]);
	}
	public function getPendingSolutions(Request $request){
		if(!(Solution::where('status', '<=', 1)->count())){
			return response()->json([]);
		}
		$solutions = Solution::leftJoin('problemsets', 'solutions.problemset_id', '=', 'problemsets.id')
				->leftJoin('users', 'solutions.user_id', '=', 'users.id')
				->where('users.bot_tendency', '<', 100)
				->where('solutions.status', '<=', 1)
				->where(function($query){
					$query->where('problemsets.type', '<>', 'oi')
					      ->orWhere('solutions.problemset_id', '<', 0)
					      ->orWhere('solutions.created_at', '<', DB::raw('problemsets.contest_start_at'))
					      ->orWhere('solutions.created_at', '>', DB::raw('problemsets.contest_end_at'));
						})
				->take(200)
				->orderBy('solutions.id','asc')
				->groupBy('solutions.user_id')
				->select(DB::raw('MIN(solutions.id) as id'));

		$solutions_oi = Solution::leftJoin('problemsets', 'solutions.problemset_id', '=', 'problemsets.id')
				->where('solutions.status', '<=', 1)
				->where('problemsets.type', 'oi')
				->where('problemsets.contest_end_at', '<', DB::raw('now()'))
				->where('solutions.created_at', '>=', DB::raw('problemsets.contest_start_at'))
				->where('solutions.created_at', '<=', DB::raw('problemsets.contest_end_at'))
				->take(5)
				->orderBy('solutions.user_id', 'asc')
				->select('solutions.id');
		$solutions = $solutions->union($solutions_oi);

		$solutions = $solutions->get();
		return response()->json($solutions);
	}
	public function postCheckout(Request $request){
		$this->validate($request,[
			"solution_id" => "required|integer",
		]);
		$solution = Solution::find($request->solution_id);
		if($solution == NULL) return response()->json(["ok" => false]);
		if($solution->status <= 1 || $request->force === "true"){
			$query = Solution::where('id', $solution->id);
			if(!$request->force){
				$query = $query->where('status', '<=', 1);
			}
			$ok = $query->update([
				'time_used' => 0,
				'memory_used' => 0.0,
				'status' => SL_COMPILING,
				'score' => 0,
				'ce' => NULL,
				'sim_id' => NULL,
				'judger_id' => \Request::get('judger')->id,
			]);

			if($ok){
				foreach($solution->testcases as $testcase){
					$testcase->delete();
				}
				return response()->json(["ok" => true]);
			}else{
				return response()->json(["ok" => false]);
			}
		}else{
			return response()->json(["ok" => false]);
		}
	}
	public function getSolution(Request $request){
		$this->validate($request,[
			"solution_id" => "required|integer",
		]);
		$solution = Solution::where("id",$request->solution_id)
			->select(["id", "problem_id", "language", "code", "time_used", "memory_used",
						"status", "score", "ce", "cnt_testcases"])
			->first();
		return response()->json($solution);
	}
	public function getProblem(Request $request){
		$this->validate($request,[
			"problem_id" => "required|integer",
		]);
		$problem = Problem::findOrFail($request->problem_id);
		$problem = Problem::where("id", $request->problem_id)
			->select("id", "name", "type", "spj", "timelimit", "memorylimit")
			->first();
		return response()->json($problem);
	}
	public function postUpdateCe(Request $request){
		$this->validate($request,[
			"solution_id" => "required|integer",
		]);
		$solution = Solution::findOrFail($request->solution_id);
		$solution->ce = $request->ce;
		$solution->save();
		return response()->json(["ok" => true]);
	}
	public function postUpdateSolution(Request $request){
		$this->validate($request,[
			"solution_id" => "required|integer",
		]);
		$solution = Solution::findOrFail($request->solution_id);

		$solution->time_used = $request->time_used;
		$solution->memory_used = $request->memory_used;
		$solution->status = $request->status;
		$solution->score = $request->score;
		$solution->cnt_testcases = $request->cnt_testcases;
		$solution->save();

		$cache_path = $solution->user_id.'-'.$solution->problemset_id.'-'.$solution->problem_id;
		if($solution->score > Cache::tags(['problemsets', 'max_score'])->get($cache_path, -1)){
			Cache::tags(['problemsets', 'max_score'])->put($cache_path, $solution->score, CACHE_ONE_DAY);
		}

		return response()->json(["ok" => true]);
	}
	public function postFinishJudging(Request $request){
		$this->validate($request,[
			"solution_id" => "required|integer",
		]);
		$solution = Solution::findOrFail($request->solution_id);

		$solution->status = SL_JUDGED;
		$solution->judged_at = date('Y-m-d H:i:s');
		
		$solution->save();

		$solution->user->update_cnt_ac();
	}
	public function postPostTestcase(Request $request){
		$testcase = Testcase::create($request->except('judger_token'));
	}

	public function getGetAnswer(Request $request){
		$solution = Solution::findOrFail($request->solution_id);
		$answer = $solution->answerfiles()->where('filename', $request->filename)->first();
		return response()->json($answer);
	}
}
