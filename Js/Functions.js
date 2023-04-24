// 用js获取当前时间为当天的第几秒
// Path: Js\Functions.js
function getSeconds() {
    var now = new Date();
    var startOfDay = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    var seconds = (now.getTime() - startOfDay.getTime()) / 1000;
    return seconds;
}

// 用js获取当前时间为当天的第几毫秒
// Path: Js\Functions.js
function getMilliseconds() {
    var now = new Date();
    var startOfDay = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    var milliseconds = now.getTime() - startOfDay.getTime();
    return milliseconds;
}

// prefix + 当天的第几毫秒 + 三位随机数，使用连接符进行拼接
// Path: Js\Functions.js
function generateRandomNumber(prefix) {
    var now = new Date();
    var startOfDay = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    var milliseconds = now.getTime() - startOfDay.getTime();
    var randomNumbers = Math.floor(Math.random() * 1000);
    return prefix + "-" + milliseconds.toString() + "-" + randomNumbers.toString().padStart(3, '0');
}

// 通过ua来获取iOS的版本号
// Path: Js\Functions.js
function getIOSVersionByUa() {
    var iOSVersion = "0";
    var ua = navigator.userAgent.toLowerCase();
    if (/iphone|ipad|ipod/.test(ua)) {
        var version = navigator.userAgent.match(/OS (\d+)_(\d+)_?(\d+)?/);
        iOSVersion = version[0].replace("OS ", "").replaceAll("_",".");
        console.log("iOS 完整版本号：" + version);
    }
    return iOSVersion;
}

// 通过ua来判断iOS是否小于等于14
// Path: Js\Functions.js
function isIOSLe14() {
    return parseFloat(getIOSVersionByUa()) <= 14;
}

// 10进制转62进制
// Path: Js\Functions.js
function decimalTo62(num) {
    var chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    var radix = chars.length;
    var qutient = +num;
    var arr = [];
    do {
        mod = qutient % radix;
        qutient = (qutient - mod) / radix;
        arr.unshift(chars[mod]);
    } while (qutient);
    return arr.join('');
}

// 62进制转10进制
// Path: Js\Functions.js
function sixtyTwoToDecimal(num) {
    var chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    var radix = chars.length;
    var len = num.length;
    var i = 0;
    var originNum = 0;
    while (i < len) {
        originNum += chars.indexOf(num[i]) * Math.pow(radix, len - i - 1);
        i++;
    }
    return originNum;
}

// 任何字符串转62进制
// Path: Js\Functions.js
function stringTo62(str) {
    var chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    var radix = chars.length;
    var hex = Array.prototype.map.call(str, function (ch) {
        var code = ch.charCodeAt(0).toString(16);
        return new Array(Math.abs(code.length - 4) + 1).join('0') + code;
    }).join('');
    var qutient = new BigInteger(hex, 16);
    var arr = [];
    do {
        mod = qutient.mod(radix);
        qutient = qutient.subtract(mod).divide(radix);
        arr.unshift(chars[mod]);
    } while (qutient.compareTo(BigInteger.ZERO) > 0);
    return arr.join('');
}

// md5_file
// Path: Js\Functions.js
function md5_file(file) {
    return new Promise(function (resolve, reject) {
        var blobSlice = File.prototype.slice || File.prototype.mozSlice || File.prototype.webkitSlice,
            chunkSize = 2097152,                             // Read in chunks of 2MB
            chunks = Math.ceil(file.size / chunkSize),
            currentChunk = 0,
            spark = new SparkMD5.ArrayBuffer(),
            fileReader = new FileReader();

        fileReader.onload = function (e) {
            console.log('read chunk nr', currentChunk + 1, 'of', chunks);
            spark.append(e.target.result);                   // Append array buffer
            currentChunk++;

            if (currentChunk < chunks) {
                loadNext();
            } else {
                console.log('finished loading');
                console.info('computed hash', spark.end());  // Compute hash
                resolve(spark.end());
            }
        };

        fileReader.onerror = function () {
            console.warn('oops, something went wrong.');
            reject();
        };

        function loadNext() {
            var start = currentChunk * chunkSize,
                end = ((start + chunkSize) >= file.size) ? file.size : start + chunkSize;

            fileReader.readAsArrayBuffer(blobSlice.call(file, start, end));
        }

        loadNext();
    });
}