<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiRequest;
use App\Jobs\OkParserApi;
use App\Models\JobInfo;
use App\Http\Resources\JobInfoResource;

class ActionController extends Controller
{
    public function __construct(private ApiRequest $request)
    {
    }

    public function __invoke(ApiRequest $request)
    {
        switch ($request->type) {
            case ApiRequest::REGISTER_JOB:
                return $this->registerJob();
            case ApiRequest::GET_JOB_INFO:
                return $this->getJobInfo();

        }
        return $this->response(data: [
            'message' => "Undefined request type",
        ], success: false);
    }

    private function getJobInfo()
    {
        $jobInfo = JobInfo::findOrFail($this->request->get('id'));
        return $this->response([
            'job' => new JobInfoResource($jobInfo)
        ]);
    }

    private function registerJob(): \Illuminate\Http\JsonResponse
    {
        $request = $this->request;
        $input = $request->input();

        $data = array_filter($input, function ($key) {
            return $key !== 'job' && $key != 'action';
        }, ARRAY_FILTER_USE_KEY);

        $jobInfo = new JobInfo([
            'status' => JobInfo::WAITING
        ]);
        $jobInfo->save();

        custom_dispatch((new OkParserApi($input['action'], $data, $jobInfo)));
    
        $jobInfo = JobInfo::find($jobInfo->id);
        return $this->response([
            'job' => new JobInfoResource($jobInfo)
        ]);
    }

    private function response(mixed $data, array $mustBeSaved = [], bool $success = true): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => $success,
            'data' => $data,
            'request' => $this->request->input(),
            'mustBeSaved' => $mustBeSaved
        ]);
    }
}
