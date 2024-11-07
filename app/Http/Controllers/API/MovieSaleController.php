<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Repositories\MovieSaleRepository;
use App\Repositories\OutputRepository;

class MovieSaleController extends Controller
{
    protected array $create_rules = [
        'theater' => 'required|exists:theaters,uuid',
        'movie' => 'required|exists:movies,uuid',
        'sale_date' => 'required|date:Y-m-d',
        'price' => 'required|money',
    ];
    protected array $update_rules = [
        'theater' => 'sometimes|exists:theaters,uuid',
        'movie' => 'sometimes|exists:movies,uuid',
        'sale_date' => 'sometimes|date:Y-m-d',
        'price' => 'sometimes|money',
    ];

    public function __construct(OutputRepository $output, MovieSaleRepository $repository)
    {
        parent::__construct();
        $this->output = $output;
        $output->setRepository($repository);
        $output->repository->setAuthUser($this->user);
    }
}
