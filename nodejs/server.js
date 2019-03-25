var SseChannel = require( 'sse-channel' );
var http       = require( 'http' );
var os         = require( 'os' );
var stompit    = require( 'stompit' );
var url        = require( 'url' );
var qs         = require( 'querystring' );
var _          = require( 'lodash' );
var winston    = require( 'winston' );
var path       = require( 'path' );
var ini        = require('node-ini');

var config     = ini.parseSync( path.resolve(__dirname, 'config.ini') );

winston.add( winston.transports.DailyRotateFile, { filename: path.resolve(__dirname, config.log.file) });
winston.level = config.log.level ;

var connectOptions = {
    'host': config.queue.host,
    'port': config.queue.port,
    'connectHeaders':{
        'host': '/',
        'login': config.queue.login,
        'passcode': config.queue.passcode,
        'heart-beat': '5000,5000'
    }
};

var subscribeHeaders = {
    'destination': config.queue.name,
    'ack': 'client-individual'
  };

var browserChannel = new SseChannel({
    retryTimeout: 250,
    historySize: 300, // XXX
    pingInterval: 15000,
    jsonEncode: true,
    cors: {
        origins: ['*'] // Defaults to []
    }
});

var generateUid = function (separator) {
    var delim = separator || "";

    function S4() {
        return (((1 + Math.random()) * 0x10000) | 0).toString(16).substring(1);
    }

    return (S4() + S4() + delim + S4() );
};

var browserLoopIntervalTime = 2000;

browserChannel.on('message', function(message) {
    // TODO: a message was sent to clients, nothing interesting to do here.
    winston.debug('browserChannel message', message);
});

browserChannel.on('disconnect', function(context, res) {
    winston.debug('browserChannel disconnect', res._clientId);
});

browserChannel.on('connect', function(context, req, res) {
    winston.debug('browserChannel connect ', res._clientId, res._matecatJobId);

    browserChannel.send({
        data : {
            _type : 'ack',
            clientId : res._clientId
        }
    }, [ res ]);
});

http.createServer(function(req, res) {
  // find job id from requested path
  var parsedUrl = url.parse( req.url ) ;
  var path = parsedUrl.path  ;

  if (path.indexOf(config.server.path) === 0 ) {
    var query = qs.parse( parsedUrl.query ) ;

    res._clientId = generateUid();
    res._matecatJobId = query.jid ;
    res._matecatPw = query.pw ;

    browserChannel.addClient(req, res);
  } else {
    res.writeHead(404);
    res.end();
  }

}).listen(config.server.port, config.server.address, function() {
  winston.debug('Listening on http://' + config.server.address + ':' + config.server.port + '/');
});

var stompMessageReceived = function( body ) {
  var message = JSON.parse( body );

  var dest = _.filter( browserChannel.connections, function( ele ) {
    if ( typeof ele._clientId == 'undefined' ) {
      return false;
    }

    var candidate = (
      ele._matecatJobId == message.data.id_job &&
      message.data.passwords.indexOf( ele._matecatPw ) !== -1  &&
      ele._clientId != message.data.id_client
    );

    if (candidate) {
      winston.debug('candidate found', ele._clientId) ;
    }

    return candidate ;
  } );

  message.data.payload._type = 'comment' ;

  browserChannel.send( {
    data: message.data.payload
  }, dest );
}

var startStompConnection = function()   {
  stompit.connect( connectOptions, function( error, client ) {

    if (typeof client === 'undefined') {
      setTimeout(startStompConnection, 10000);
      winston.debug("** client error, restarting connection in 10 seconds", error);
      return;
    }

    client.subscribe(subscribeHeaders, function(error, message) {
      winston.debug('** event received in client subscription');

      if ( error ) {
        winston.debug('!! subscribe error ' + error.message);

        client.disconnect();
        startStompConnection();

        return;
      }

      message.readString( 'utf-8', function(error, body) {

        if ( error ) {
          winston.debug('!! read message error ' + error.message);
          return;
        }
        else {
          stompMessageReceived(body);
          message.ack();
        }
      } );
    });
  } );
}

startStompConnection();
