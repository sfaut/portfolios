<?php

namespace App\Http\Controllers;

use App\Http\Requests\PortfolioRequest;
use App\Models\Portfolio;

class PortfolioController extends Controller
{
    public function index()
    {
        $portfolios = auth()->user()->portfolios()->get();

        return view('portfolio.index', [
            'portfolios' => $portfolios,
        ]);
    }

    public function create()
    {
        return view('portfolio.create');
    }

    public function store(PortfolioRequest $request)
    {
        $data = $request->validated();

        auth()->user()->portfolios()->create($data);

        return to_route('portfolio.index')
            ->with('message', trans('portfolio.store.success', ['name' => $data['name']]));
    }

    public function show(Portfolio $portfolio)
    {
        return view('portfolio.show', [
            'portfolio' => $portfolio,
        ]);
    }

    public function edit(Portfolio $portfolio)
    {
        return view('portfolio.edit', [
            'portfolio' => $portfolio,
        ]);
    }

    public function update(PortfolioRequest $request, Portfolio $portfolio)
    {
        $data = $request->validated();

        $portfolio->update($data);

        return to_route('portfolio.show', $portfolio)
            ->with('message', trans('portfolio.update.success', ['name' => $portfolio->name]));
    }

    public function destroy(Portfolio $portfolio)
    {
        $portfolio->delete();

        return to_route('portfolio.index')
            ->with('message', trans('portfolio.destroy.success', ['name' => $portfolio->name]));
    }
}
