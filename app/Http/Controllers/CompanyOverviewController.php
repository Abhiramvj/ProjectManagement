<?php

namespace App\Http\Controllers;

use App\Actions\Company\GetCompanyInfoAction;
use App\Actions\Company\GetCompanyOverviewDataAction;
use App\Actions\Company\GetHierarchyDataAction;
use Inertia\Inertia;

class CompanyOverviewController extends Controller
{
    public function index(GetCompanyInfoAction $infoAction, GetCompanyOverviewDataAction $overviewAction, GetHierarchyDataAction $hierarchyAction
    ) {

        $companyInfo = $infoAction->execute();

        $overviewData = $overviewAction->execute();

        $hierarchyData = $hierarchyAction->execute();

        return Inertia::render('CompanyOverview/Index', array_merge(
            ['companyInfo' => $companyInfo],
            $overviewData,
            $hierarchyData
        ));
    }
}
