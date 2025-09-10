<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelController extends Controller
{
    protected $filePath;

    public function __construct()
    {
        // Path to your Excel file (ensure file exists and is writable)
        $this->filePath = storage_path('app/public/reviews.xlsx');
    }

    // Load the Excel sheet data and return as JSON
    public function getSheetData()
    {
        try {
            if (!file_exists($this->filePath)) {
                \Log::error('Excel file not found at path: ' . $this->filePath);
                return response()->json(['error' => 'Excel file not found.'], 404);
            }

            $spreadsheet = IOFactory::load($this->filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $sheetName = $sheet->getTitle();

            $rows = [];
            foreach ($sheet->getRowIterator() as $row) {
                $cells = [];
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                foreach ($cellIterator as $cell) {
                    // Use empty string for nulls to keep grid consistent
                    $cells[] = $cell ? $cell->getValue() : '';
                }
                $rows[] = $cells;
            }

            return response()->json([
                'sheetName' => $sheetName,
                'rows' => $rows
            ]);
        } catch (\Exception $e) {
            \Log::error('Error loading Excel sheet data: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load Excel data: ' . $e->getMessage()], 500);
        }
    }

    // Save a single cell's value and update the Excel file
    public function saveCellData(Request $request)
    {
        try {
            $validated = $request->validate([
                'sheetName' => 'required|string',
                'row' => 'required|integer|min:0', // 0-based from frontend
                'col' => 'required|integer|min:0', // 0-based from frontend
                'value' => 'nullable',
            ]);

            if (!file_exists($this->filePath)) {
                \Log::error('Excel file not found at path: ' . $this->filePath);
                return response()->json(['error' => 'Excel file not found.'], 404);
            }

            $spreadsheet = IOFactory::load($this->filePath);

            // Try to get the sheet by name, fallback to active
            $sheet = $spreadsheet->getSheetByName($validated['sheetName']);
            if (!$sheet) {
                $sheet = $spreadsheet->getActiveSheet();
            }

            // Convert 0-based indices to 1-based for PhpSpreadsheet
            $excelRow = $validated['row'] + 1;
            $excelCol = $validated['col'] + 1;

            // Update cell
            $sheet->setCellValueByColumnAndRow($excelCol, $excelRow, $validated['value']);

            // Save file (make sure write permissions are set)
            $writer = new Xlsx($spreadsheet);
            $writer->save($this->filePath);

            return response()->json([
                'success' => true,
                'message' => 'Cell updated successfully',
                'data' => [
                    'row' => $validated['row'],
                    'col' => $validated['col'],
                    'value' => $validated['value']
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error saving cell data: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to save cell data: ' . $e->getMessage()], 500);
        }
    }
}
