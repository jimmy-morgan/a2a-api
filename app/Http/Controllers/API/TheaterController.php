<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Repositories\TheaterRepository;
use App\Repositories\OutputRepository;

class TheaterController extends Controller
{
    protected array $create_rules = [
        'name' => 'required|max:255',
        'address' => 'required|max:255',
    ];
    protected array $update_rules = [
        'name' => 'sometimes|max:255',
        'address' => 'sometimes|max:255',
    ];

    public function __construct(OutputRepository $output, TheaterRepository $repository)
    {
        parent::__construct();
        $this->output = $output;
        $output->setRepository($repository);
        $output->repository->setAuthUser($this->user);
    }
}
