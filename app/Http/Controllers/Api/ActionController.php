<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiRequest;
use App\Jobs\OkParserApi;
use App\Models\FailedJob;
use App\Models\Job;
use App\Models\JobOutput;
use App\Services\OKApi;

class ActionController extends Controller
{

    public function __construct(private ApiRequest $request) {}

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


    private function getJobInfo() {
        $input = $this->request->input();
        $job = Job::find($input['id']);
        $result = JobOutput::where('job_id', $input['id'])->first();

        $answer = [];

        if(!$job) {
            if(!isset($input['uuid'])) {
                return $this->response(data: [
                    'message' => "Job is failed, for more info send UUID"
                ], success: false);
            }
            $uuid = $input['uuid'];
            $job = FailedJob::where('uuid', $uuid)->first();

            if($result) {
                $answer['result'] = $result->result;
            }

            return $this->response(array_merge([
                'job' => $job,
            ], $answer));
        }

        return $this->response([
            'job' => $job->load('result'),
        ], [
            'job_id' => $job->id,
            'job_uuid' => $job->payload['uuid']
        ]);
    }

    private function response(array $data, array $mustBeSaved = [], bool $success = true): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => $success,
            'data' => $data,
            'request' => $this->request->input(),
            'mustBeSaved' => $mustBeSaved
        ]);
    }

    private function registerJob(): \Illuminate\Http\JsonResponse
    {
        $request = $this->request;
        $input = $request->input();

        $data = array_filter($input, function ($key) {
            return $key !== 'job' && $key != 'action';
        }, ARRAY_FILTER_USE_KEY);

        $jobId = custom_dispatch((new OkParserApi($input['action'], $data, new OKApi())));
        $job = Job::find($jobId);

        return $this->response([
            'job' => $job,
        ], [
            'job_id' => $jobId,
            'job_uuid' => $job->payload['uuid']
        ]);
    }

}
