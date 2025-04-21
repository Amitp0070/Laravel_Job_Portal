<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        if ($request->sort == "oldest") {
            $jobs = $jobs->orderBy('created_at', 'ASC');
        } else {
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

    public function detail($id)
    {
        $job = Job::where(['id' => $id, 'status' => 1])->with(['jobType', 'category'])->first();

        if ($job == null) {
            abort(404);
        }
        return view('front.jobDetail', ['job' => $job]);
    }

    public function applyJob(Request $request)
    {
        $id = $request->id;
        $job = Job::where('id', $id)->first();

        // If job not found in db
        if ($job == null) {
            return response()->json([
                'status' => false,
                'message' => 'Job not found!'
            ]);
        }

        // You cannot apply on your own job
        $employer_id = $job->user_id;
        if ($employer_id == Auth::user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'You cannot apply on your own job!'
            ]);
        }

        $jobApplicationCount = JobApplication::where([
            'user_id' => Auth::user()->id,
            'job_id' => $id
        ])->count();

        // If already applied for this job
        if ($jobApplicationCount > 0) {
            return response()->json([
                'status' => false,
                'message' => 'You have already applied for this job!'
            ]);
        }

        $application = new JobApplication();
        $application->job_id = $id;
        $application->user_id = Auth::user()->id;
        $application->employer_id = $employer_id;
        $application->applied_date = now();
        $application->save();

        return response()->json([
            'status' => true,
            'message' => 'Job applied successfully!'
        ]);
    }
}
