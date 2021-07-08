<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Book;
use App\RentBook;
use App\RentBookLog;
use App\User;

class BookController extends Controller
{
    //Function for getting all books list
    public function index()
    {
        $books = Book::all();
        return response()->json([
            "success" => true,
            "message" => "Books List",
            "data" => $books
        ]);
    }

    /**
    * Function for create new Book record
    *
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\Response
    */
    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(),[ 
            'book_name' => 'required|string|min:2|max:255|unique:books',
            'author' => 'required|string|min:2|max:255',
            'cover_image' => 'required|image:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 422);      
        }

        $uploadFolder = \Config::get('books.upload_folder');
        $image = $request->file('cover_image');
        $image_uploaded_path = $image->store($uploadFolder, 'public');
        
        $book = Book::create([
            'book_name' => $request->book_name,
            'author' => $request->author,
            'cover_image' => basename($image_uploaded_path)
        ]);

        return response()->json([
            "success" => true,
            "message" => "Book created successfully.",
            "data" => $book
        ]);
    } 

    /**
    * Function for Display the specified book.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function show($id)
    {
        $book = Book::where('b_id',$id)->first();
        if (is_null($book)) {
            return response()->json('Book not found', 404);
        }
        return response()->json([
            "success" => true,
            "message" => "Book retrieved successfully.",
            "data" => $book
        ]);
    }

    /**
    * Function for Update the specified book in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function update(Request $request, $id)
    {
        $book = Book::where('b_id',$id)->first();
        if (is_null($book)) {
            return response()->json('Book not found', 404);
        }

        $validator = \Validator::make($request->all(),[ 
            'author' => 'required|string|min:2|max:255',
            'cover_image' => 'required|image:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 422);      
        }

        $uploadFolder = \Config::get('books.upload_folder');
        $image = $request->file('cover_image');
        $image_uploaded_path = $image->store($uploadFolder, 'public');
        
        Book::where('b_id',$id)->update(['author' => $request->author, 'cover_image' => basename($image_uploaded_path)]);
        $book = Book::where('b_id',$id)->first();
        return response()->json([
            "success" => true,
            "message" => "Book updated successfully.",
            "data" => $book
        ]);
    }

    /**
    * Remove the specified book from storage.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function destroy($id)
    {
        $book = Book::where('b_id',$id)->first();
        if (is_null($book)) {
            return response()->json('Book not found', 404);
        }
        
        Book::where('b_id',$id)->delete();
        return response()->json([
            "success" => true,
            "message" => "Book deleted successfully.",
        ]);
    }

    //Function for rent book from user
    public function rentBook(Request $request)
    {
        $validator = \Validator::make($request->all(),[ 
            'b_id' => 'required|numeric'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 422);      
        }

        $rentBook = RentBook::where('u_id',$request->userId)->where('b_id',$request->b_id)->first();
        if(!is_null($rentBook)){
            return response()->json('Book already rented', 400);
        }

        $rentBook = RentBook::where('b_id',$request->b_id)->first();
        if(!is_null($rentBook)){
            return response()->json('Book rented by another user', 400);
        }

        \DB::beginTransaction();
        try {
            //Add rent book record in rent_books table
            $rentBook = new RentBook;
            $rentBook->u_id = $request->userId;
            $rentBook->b_id = $request->b_id;
            $rentBook->save();

            //Add rent book record in rent_book_logs table
            $rentBookLog = new RentBookLog;
            $rentBookLog->u_id = $request->userId;
            $rentBookLog->b_id = $request->b_id;
            $rentBookLog->save();

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollback();
            throw $e;
        }

        return response()->json([
            "success" => true,
            "message" => "Book rented successfully.",
        ]);
    }

    //Function for return rent book from user
    public function returnBook(Request $request)
    {
        $validator = \Validator::make($request->all(),[ 
            'b_id' => 'required|numeric'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 422);      
        }

        $rentBook = RentBook::where('u_id',$request->userId)->where('b_id',$request->b_id)->first();
        if(is_null($rentBook)){
            return response()->json('Rent Book entry not found', 400);
        }

        \DB::beginTransaction();
        try {
            //delete rent book record from rent_book table
            $rentBook->delete();

            //Update return_date in rent_book_logs table
            $rentBookLog = RentBookLog::where('u_id',$request->userId)->where('b_id',$request->b_id)->whereNull('return_date')->first();
            $rentBookLog->return_date = date('Y-m-d H:i:s');
            $rentBookLog->save();

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollback();
            throw $e;
        }

        return response()->json([
            "success" => true,
            "message" => "Book returned successfully.",
        ]);
    }

    //Function for getting userwise rented books list
    public function getRentedBooks(Request $request)
    {
        $userIds = RentBook::select('u_id')->distinct()->pluck('u_id');
        $users = User::whereIn('u_id',$userIds)->get();
        foreach($users as $user){
            $books = RentBook::select('books.b_id','books.book_name','books.author','books.cover_image','rent_books.created_at as rent_date')->join('books', function($join) {
                $join->on('rent_books.b_id', '=', 'books.b_id');
              })->where('u_id',$user->u_id)->get();
              $user['rent_books'] = $books;        
        }
        
        return response()->json([
            "success" => true,
            "message" => "Books List",
            "data" => $users
        ]);
    }
}
