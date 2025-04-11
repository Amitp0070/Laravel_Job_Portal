<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Job;
use App\Models\JobType;
use Illuminate\Http\Request;

class JobsController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::where('status', 1)->get();
        $jobTypes = JobType::where('status', 1)->get();
        $jobs = Job::where('status', 1);

        if (!empty($request->keyword)) {
            $jobs = $jobs->where(function ($query) use ($request) {
                $query->orWhere('title', 'Like', '%' . $request->keyword . '%');
                $query->orWhere('keywords', 'Like', '%' . $request->keyword . '%');
            });
        }
        if (!empty($request->location)) {
            $jobs = $jobs->where('location', $request->location);
        }
        if (!empty($request->category)) {
            $jobs = $jobs->where('category_id', $request->category);
        }
        $jobTypeArray = [];
        if (!empty($request->jobType)) {
            $jobTypeArray = explode(',', $request->jobType);
            $jobs = $jobs->whereIn('job_type_id', $jobTypeArray);
        }
        if (!empty($request->experience)) {
            $jobs = $jobs->where('experience', $request->experience);
        }
        
        $jobs = $jobs->with(['jobType', 'category']);

        if($request->sort == "oldest"){
            $jobs = $jobs->orderBy('created_at', 'ASC');
        }else{
            $jobs = $jobs->orderBy('created_at', 'DESC');
        }
        $jobs = $jobs->paginate(9);
        return view(
            'front.jobs',
            [
                'categories' => $categories,
                'jobTypes' => $jobTypes,
                'jobs' => $jobs,
                'jobTypeArray' => $jobTypeArray,
            ]
        );
    }
}
