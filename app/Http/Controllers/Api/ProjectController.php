<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    // List semua project
    public function index()
    {
        $projects = Project::orderBy('created_at', 'desc')->get();
        return response()->json($projects);
    }

    // Simpan project baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_customer' => 'required|string|max:255',
            'nama_project' => 'required|string|max:255',
            'lokasi' => 'nullable|string|max:255',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date',
            'anggaran_kontrak' => 'required|numeric|min:0',
            'status_project' => 'required|in:Berjalan,Selesai,Batal',
            'deskripsi' => 'nullable|string',
        ]);

        $project = Project::create($validated);

        return response()->json([
            'message' => 'Project berhasil dibuat',
            'data' => $project,
        ], 201);
    }

    // Update project
    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'nama_customer' => 'required|string|max:255',
            'nama_project' => 'required|string|max:255',
            'lokasi' => 'nullable|string|max:255',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date',
            'anggaran_kontrak' => 'required|numeric|min:0',
            'status_project' => 'required|in:Berjalan,Selesai,Batal',
            'deskripsi' => 'nullable|string',
        ]);

        $project->update($validated);

        return response()->json([
            'message' => 'Project berhasil diupdate',
            'data' => $project,
        ]);
    }

    // Hapus project
    public function destroy(Project $project)
    {
        $project->delete();

        return response()->json([
            'message' => 'Project berhasil dihapus',
        ]);
    }

    // Tampilkan detail satu project
    public function show(Project $project)
    {
        return response()->json($project);
    }
}
