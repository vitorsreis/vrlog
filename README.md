# VRLog - PHP Full AccessLog
Simple library to log hits in your php code, logging input, output, errors and extras.

## Install
    composer require vitorsreis/vrlog

## Simple usage
    require_once __DIR__ . "/vendor/autoload.php";
    VRLog\VRLog::bootstrap();

## Default log input values
|    Key     | Type    | Adaptor                                      |
|:----------:|---------|----------------------------------------------|
| start_date | string  | Start date (YYYY-MM-DDTHH:mm:ss+00:00Z)      |
| start_time | float   | Start timestamp                              |
|   method   | string  | Request Method                               |
|    url     | array   | URL (scheme:string, host:string, uri:string) |
|     ip     | string  | Remote IP                                    |
|  referer   | ?string | Remote Referer                               |
| useragent  | ?string | Remote User-Agent                            |
|    get     | ?array  | Query data                                   |
|    post    | ?array  | Post data                                    |
|  rawpost   | ?array  | Post raw data (php://input)                  |
|   files    | ?array  | Files data                                   |
|  cookies   | ?array  | Cookies data                                 |
|   server   | ?array  | Server data                                  |

## Default log output values
|    Key    | Type   | Adaptor                               |
|:---------:|--------|---------------------------------------|
| end_date  | string | End date (YYYY-MM-DDTHH:mm:ss+00:00Z) |
| end_time  | float  | End timestamp                         |
|   time    | float  | Total request time                    |
| http_code | int    | Response Code                         |
|  headers  | ?array | Response Headers                      |
|   error   | ?array | Errors/Excpetions                     |
|   extra   | ?array | Extra data                            |
| inc_files | ?array | Included files                        |

## Add extra values using
    VRLog\VRLog::extra("name", "john");
    VRLog\VRLog::extra("name", "lara");
    
    output in log:
    {... "extra":{"name":["john","lara"]} }

## Adaptor available
| Status | Key           | Adaptor       |
|:------:|---------------|---------------|
|   ☑    | file          | File          |
|   ☑    | elasticsearch | ElasticSearch |