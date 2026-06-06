<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    // GET
    public function index()
    {
        return response()->json(Book::all());
    }

    // POST
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'judul' => 'required|string|max:255',
            'penulis' => 'required|string|max:255',
            'tahunterbit' => 'required|numeric|digits:4',
        ]);

        $book = Book::create($validatedData);

        return response()->json($book, 201);
    }

    // GET by ID
    public function show($id)
    {
        return response()->json(Book::findOrFail($id));
    }

    // PUT / PATCH
    public function update(Request $request, $id)
    {
        $book = Book::findOrFail($id);
        $book->update($request->all());
        return response()->json($book);
    }

    // DELETE
    public function destroy($id)
    {
        Book::destroy($id);
        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}
