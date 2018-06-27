/**
 *
 * @package     GZip Plugin
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

import {cacheFirst} from "./sw.strategies.cache_first.js";
import {cacheNetwork} from "./sw.strategies.cache_network.js";
import {cacheOnly} from "./sw.strategies.cache_only.js";
import {networkFirst} from "./sw.strategies.network_first.js";
import {networkOnly} from "./sw.strategies.network_only.js";
import {strategies} from "./sw.strategies.js";

strategies.add("cf", cacheFirst, "Cache fallback to Network");
strategies.add("cn", cacheNetwork, "Cache and Network Update");
strategies.add("co", cacheOnly, "Cache Only");
strategies.add("nf", networkFirst, "Network fallback to Cache");
strategies.add("no", networkOnly, "Network Only");

export {strategies};
