// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}
/*global Icinga:false, document: false, define:false require:false base_url:false console:false, window:false */

define(function() {
    'use strict';

    function logTagged(tag, logArgs) {
        var now = new Date();
        var ms = now.getMilliseconds() + '';
        while (ms.length < length) {
            ms = '0' + ms;
        }
        logArgs = [].slice.call(logArgs);
        logArgs.unshift(now.toLocaleTimeString() + '.' + ms);

        var args = [tag.toUpperCase() + ' :'];
        for (var el in logArgs) {
            args.push(logArgs[el]);
        }

        try {
            if (console[tag]) {

                console[tag].apply(console,logArgs);
            } else {
                console.log.apply(console,args);
            }

        } catch (e) { // IE fallback
            console.log(logArgs);
        }
    }

    if(!window.console) {
        window.console = { log: function() {} };
    }
    var features = {
        debug: function() {
            if (!window.ICINGA_DEBUG) {
                return;
            }
            logTagged('debug', arguments);
        },
        warn: function() {
            logTagged('warn', arguments);
        },
        error: function() {
            logTagged('error', arguments);
        },
        emergency: function() {
            logTagged('emergency', arguments);
            // TODO: log *emergency* errors to the backend
        }
    };

    return features;
});
