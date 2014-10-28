<?php 

$this->slim->get('/hello', function () {
	$this->slim->response()->header('Content-Type', 'application/json');
	$this->slim->log->debug("Passing by /hello route");
	$this->slim->response()->status(HTTP_OK);
	$result = json_encode(array(
    	"content" => "Hello, it works!",
    	"error" => 0
    	));
    $this->slim->response()->body($result);
});

$this->slim->get('/hello/:name', function ($name) {
	$this->slim->log->debug("Passing by /hello/$name route");
	$result = "Hello, $name";
    $this->slim->response()->body($result);
});

$this->slim->get('/404', function () {
	$this->slim->log->debug("Passing by /404 route");
	$this->slim->response()->status(HTTP_NOT_FOUND);
});

$this->slim->get('/400', function () {
	$this->slim->log->debug("Passing by /400/$reason route");
	$this->slim->response()->status(HTTP_BAD_REQUEST);
	$this->slim->response()->header('X-Status-Reason', "This is an invalid route");
});

$this->slim->get('/users', function () {

});

$this->slim->get('/users/:id', function ($id) {

});

$this->slim->post('/users/:id', function ($id) {

});

$this->slim->put('/users/:id', function ($id) {

});

$this->slim->delete('/users/:id', function ($id) {

});



// try {
//     if (true) {
//       // if found, return JSON response
//       $this->slim->response()->header('Content-Type', 'application/json');
//       echo json_encode($result);
//     } else {
//       // else throw exception
//       throw new ResourceNotFoundException();
//     }
// } catch (ResourceNotFoundException $e) {
// 	// return 404 server error
// 	$this->slim->response()->status(404);
// } catch (Exception $e) {
// 	$this->slim->response()->status(400);
// 	$this->slim->response()->header('X-Status-Reason', $e->getMessage());
// }