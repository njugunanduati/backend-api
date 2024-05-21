<?php

namespace App\Http\Controllers;

use Validator;
use Notification;
use App\Models\Ticket;
use App\Jobs\ProcessEmail;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Notifications\TicketSent;

use App\Http\Controllers\Controller;
use App\Http\Requests\TicketRequest;
use Illuminate\Notifications\Notifiable;
use App\Http\Resources\Ticket as TicketResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;



class TicketController extends Controller
{
    use ApiResponses, Notifiable;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $tickets = Ticket::all(); //Get all Tickets by assessment_id

            $transform = TicketResource::collection($tickets);

            return $this->successResponse($transform, 200);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Ticket not found', 400);
        }
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'email' => 'required|string|email',
                'priority' => 'required|string',
                'type' => 'required|string',
                'subject' => 'required|string',
                'description' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $ticket = Ticket::create($request->all());
            $notice = [
                'first_name' => trimSpecial(strip_tags($request->first_name)),
                'last_name' => trimSpecial(strip_tags($request->last_name)),
                'to' => env('EMAIL_TECHNICAL_SUPPORT_TO'),
                'from' => trim($request->email),
                'message' => trimSpecial(strip_tags($request->description)),
                'ticket_type' => $request->type,
                'priority' => $request->priority,
                'subject' => trimSpecial(strip_tags($request->subject)),
                'copy' => '',
            ];
            ProcessEmail::dispatch($notice, 'ticket');
        
            $transform = new TicketResource($ticket);

            return $this->showMessage($transform, 201);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Ticket not found', 400);
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Ticket  $ticket
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        try {
            $ticket = Ticket::findOrFail($id);

            $transform = new TicketResource($ticket);

            return $this->successResponse($transform, 200);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Ticket not found', 400);
        }
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Ticket  $ticket
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'email' => 'required|string|email',
                'priority' => 'required|string',
                'type' => 'required|string',
                'subject' => 'required|string',
                'description' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $ticket = Ticket::findOrFail($id);
            $ticket->first_name = $request->first_name;
            $ticket->last_name = $request->last_name;
            $ticket->email = $request->email;
            $ticket->priority = $request->priority;
            $ticket->type = $request->type;
            $ticket->subject = $request->subject;
            $ticket->description = $request->description;

            if ($ticket->isDirty()) {
                $ticket->save();
            }

            $transform = new TicketResource($ticket);
            return $this->showMessage($transform, 201);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Ticket not found', 400);
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Ticket  $ticket
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $ticket = Ticket::findOrFail($id);
            $ticket->delete();

            return $this->singleMessage('Ticket Deleted', 202);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Ticket not found', 400);
        }
    }
}
