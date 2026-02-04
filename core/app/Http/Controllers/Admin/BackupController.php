<?php

namespace App\Http\Controllers\Admin;

use App\Models\Backup;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class BackupController extends Controller
{
    public function index() {
        $data['backups'] = Backup::orderBy('id', 'DESC')->paginate(10);
        return view('admin.backup', $data);
    }

    public function store() {

        $filename = uniqid() . '.sql';

        $process = new Process((array)sprintf(
            'mysqldump -u%s -p%s %s > %s',
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password'),
            config('database.connections.mysql.database'),
            'core/storage/app/public/' . $filename
        ));

        $process->mustRun();

        $backup = new Backup;
        $backup->filename = $filename;
        $backup->save();

        $notification = array(
            'messege' => 'Backup saved successfully',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);
    }

    public function download(Request $request) {
        return response()->download('core/storage/app/public/'.$request->filename, 'backup.sql');
    }

    public function delete($locale, $id) {
        $backup = Backup::find($id);
        @unlink('core/storage/app/public/'.$backup->filename);
        $backup->delete();

        $notification = array(
            'messege' => 'Database sql file deleted successfully!',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);
    }
}
