<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\File;

class DatabaseBackupController extends Controller
{

    public function index(){
        return Inertia::render('settings/DatabaseBackup');
    }
    public function generate(Request $request)
    {
        $db = config('database.connections.mysql');
        $filename = 'backup-' . now()->format('Y-m-d_H-i-s') . '.sql';
        $path = storage_path("app/backups/{$filename}");

        $tablas = $request->input('tablas', []);
        $command = [
            'mysqldump',
            '-u' . $db['username'],
            '-p' . $db['password'],
            '-h' . $db['host'],
            $db['database']
        ];

        if (!empty($tablas)) {
            $command = array_merge($command, $tablas);
        }

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        File::ensureDirectoryExists(storage_path('app/backups'));
        File::put($path, $process->getOutput());

        return response()->download($path, $filename)->deleteFileAfterSend(false);
    }

    public function listBackups()
    {
        $files = File::files(storage_path('app/backups'));

        return collect($files)->map(function ($file) {
            return [
                'name' => $file->getFilename(),
                'size_kb' => round($file->getSize() / 1024, 2),
                'created_at' => $file->getCTime(),
            ];
        })->sortByDesc('created_at')->values();
    }

    public function downloadBackup($filename)
    {
        $path = storage_path("app/backups/{$filename}");

        if (!File::exists($path)) {
            abort(404, 'Archivo no encontrado');
        }

        return response()->download($path);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:sql,txt',
        ]);

        $file = $request->file('file')->getPathname();
        $db = config('database.connections.mysql');

        $command = sprintf(
            'mysql -u%s -p%s -h%s %s < %s',
            escapeshellarg($db['username']),
            escapeshellarg($db['password']),
            escapeshellarg($db['host']),
            escapeshellarg($db['database']),
            escapeshellarg($file)
        );

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return response()->json(['message' => 'Importación completada']);
    }

    public function tables()
    {
        $tables = DB::select('SHOW TABLES');
        $key = 'Tables_in_' . DB::getDatabaseName();
        return collect($tables)->pluck($key);
    }
}
