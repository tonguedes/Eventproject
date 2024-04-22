<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Event;
use App\Models\User;

class EventController extends Controller
{

    public function index()
    {

        $search = request('search');

        if ($search) {

            $events = Event::where([
                ['title', 'like', '%' . $search . '%']
            ])->get();
        } else {
            $events = Event::all();
        }

        return view('welcome', ['events' => $events, 'search' => $search]);
    }

    public function create()
    {
        return view('events.create');
    }

    public function store(Request $request)
    {

        $event = new Event;

        $event->title = $request->title;
        $event->date = $request->date;
        $event->city = $request->city;
        $event->private = $request->private;
        $event->description = $request->description;
        $event->items = $request->items;

        // Image Upload
        if ($request->hasFile('image') && $request->file('image')->isValid()) {

            $requestImage = $request->image;

            $extension = $requestImage->extension();

            $imageName = md5($requestImage->getClientOriginalName() . strtotime("now")) . "." . $extension;

            $requestImage->move(public_path('img/events'), $imageName);

            $event->image = $imageName;
        }

        //prennchando o campo user_id
        $user = auth()->user();
        $event->user_id = $user->id;

        $event->save();

        return redirect('/')->with('msg', 'Evento criado com sucesso!');
    }

    public function show($id)
    {

        $event = Event::findOrFail($id);

        //saber quem e o dono do Evento:
        $eventOwner = User::where('id', $event->user_id)->first()->toArray();

        return view('events.show', ['event' => $event, 'eventOwner' => $eventOwner]);
    }

    public function dashboard()
    {
        $user = auth()->user();
        $events = $user->events;
        /*tenndo acesso aos eventos que o usuario participa*/
        $eventAsParticipant = $user->eventAsParticipant;


        return view('events.dashboard', ['events' => $events, 'eventasparticipant' => $eventAsParticipant]);
    }

    public  function destroy($id)
    {
        Event::findOrFail($id)->delete();
        return redirect('/dashboard')->with('msg', 'Evento Excluido com sucesso');
    }

    public function edit($id)
    {    $user = auth()->user();
        $event =  Event::findOrFail($id);

        if ($user->id !=$event->user_id) {
            return redirect('dashboard');
        }

        return view('events.edit', ['event' => $event]);
    }

    public function update(Request $request)
    {
        $data = $request->all();

        // Image Upload
        if ($request->hasFile('image') && $request->file('image')->isValid()) {

            $requestImage = $request->image;

            $extension = $requestImage->extension();

            $imageName = md5($requestImage->getClientOriginalName() . strtotime("now")) . "." . $extension;

            $requestImage->move(public_path('img/events'), $imageName);

            $data['image'] =  $imageName;
        };
        Event::findOrFail($request->id)->update($data);
        return redirect('/dashboard')
            ->with('msg', 'Editado');
    }

    public function joinEvent($id){
        $user = auth()->user();
        $user->eventAsParticipant()->attach($id);
        $event = Event::findOrFail($id);
        return redirect('/dashboard')->with('msg','Sua presença esta confirmada no eventp'.$event->title);
    }
}
