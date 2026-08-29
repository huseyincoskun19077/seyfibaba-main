
import 'dart:async';
import 'dart:convert';
import 'dart:developer';
import 'dart:io';

import 'package:http/http.dart' as http;

import '../../../modules/authentication/models/auth_error_model.dart';
import '../../error/exception.dart';
import 'remote_data_source.dart';

class NetworkParser {
  static const _className = 'RemoteDataSourceImpl';

  static Future<dynamic> callClientWithCatchException(
      CallClientMethod callClientMethod) async {
    try {
      final response = await callClientMethod();
      log(response.statusCode.toString(), name: _className);
      if (response.body.length <= 1500) {
        log(response.body, name: _className);
      } else {
        log('body ${response.body.length} chars', name: _className);
      }
      return _responseParser(response);
    } on SocketException {
      log('SocketException', name: _className);
      throw const NetworkException('No internet connection', 10061);
    } on FormatException {
      log('FormatException', name: _className);
      throw const DataFormatException('Data format exception', 422);
    } on TimeoutException {
      log('TimeoutException', name: _className);
      throw const NetworkException('Request timeout', 408);
    } on http.ClientException {
      ///503 Service Unavailable
      log('http ClientException', name: _className);
      throw const NetworkException('Service unavailable', 503);
    }
  }

  static _responseParser(http.Response response) {
    switch (response.statusCode) {
      case 200:
      case 201:
        var responseJson = json.decode(response.body);
        return responseJson;
      case 400:
        final errorMsg = parsingDoseNotExist(response.body);
        throw BadRequestException(errorMsg, 400);
      case 401:
        final errorMsg = parsingDoseNotExist(response.body);
        throw UnauthorisedException(errorMsg, 401);
      case 402:
        final errorMsg = parsingDoseNotExist(response.body);
        throw UnauthorisedException(errorMsg, 402);
      case 403:
        final errorMsg = parsingDoseNotExist(response.body);
        throw UnauthorisedException(errorMsg, 403);
      case 404:
        final errorMsg = parsingDoseNotExist(response.body);
        throw UnauthorisedException(
            errorMsg.isNotEmpty ? errorMsg : 'Request not found', 404);
      case 429:
        final errorMsg = parsingDoseNotExist(response.body);
        throw UnauthorisedException(
            errorMsg.isNotEmpty ? errorMsg : 'Lütfen bekleyin', 429);
      case 405:
        throw const UnauthorisedException('Method not allowed', 405);
      case 408:

      ///408 Request Timeout
        throw const NetworkException('Request timeout', 408);
      case 415:

      /// 415 Unsupported Media Type
        throw const DataFormatException('Data formate exception');

      case 422:

      ///Unprocessable Entity
        final errorMsg = parsingError(response.body);
        throw InvalidInputException(Errors.fromMap(errorMsg), 422);
      case 500:

      ///500 Internal Server Error
        final errorMsg = _serverErrorMessage(response.body);
        throw InternalServerException(errorMsg, 500);

      default:
        throw FetchDataException(
            'Error occur while communication with Server', response.statusCode);
    }
  }

  static parsingError(String body) {
    final errorsMap = json.decode(body);
    try {
      if (errorsMap is Map && errorsMap['errors'] is Map) {
        return errorsMap['errors'];
      }
      if (errorsMap is Map && errorsMap['message'] != null) {
        return {
          'message': [errorsMap['message'].toString()],
        };
      }
    } catch (e) {
      log(e.toString(), name: _className);
    }

    return {
      'message': ['Unknown error'],
    };
  }

  static String parsingDoseNotExist(String body) {
    final errorsMap = json.decode(body);
    try {
      if (errorsMap['notification'] != null) {
        return errorsMap['notification'];
      }
      if (errorsMap['message'] != null) {
        return errorsMap['message'];
      }
    } catch (e) {
      log(e.toString(), name: _className);
    }
    return 'Credentials does not match';
  }

  static String _serverErrorMessage(String body) {
    try {
      final errorsMap = json.decode(body) as Map<String, dynamic>;
      final detail = errorsMap['error']?.toString().trim();
      final message = errorsMap['message']?.toString().trim();
      if (detail != null && detail.isNotEmpty && message != null && message.isNotEmpty) {
        return '$message ($detail)';
      }
      if (message != null && message.isNotEmpty) {
        return message;
      }
      if (detail != null && detail.isNotEmpty) {
        return detail;
      }
    } catch (e) {
      log(e.toString(), name: _className);
    }
    return 'Internal server error';
  }
}

