<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Repositories\MovieRepository;
use App\Repositories\OutputRepository;

class MovieController extends Controller
{
    protected array $create_rules = [
        'name' => 'required|max:255',
        'description' => 'required',
    ];
    protected array $update_rules = [
        'name' => 'sometimes|max:255',
        'description' => 'sometimes',
    ];

    public function __construct(OutputRepository $output, MovieRepository $repository)
    {
        parent::__construct();
        $this->output = $output;
        $output->setRepository($repository);
        $output->repository->setAuthUser($this->user);
    }
}
