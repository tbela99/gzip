// @ts-check
/* eslint wrap-iife: 0 */
/* global SW, BroadcastChannel */
// untested code
SW.message = {
    post (data, resolve, reject) {

    const messageChannel = new MessageChannel();
    messageChannel.port1.onmessage = function(event) {

      if (event.data.error) {

		if (reject) {

			reject(event.data.error);
		}
      }

      else {

		if (resolve) {

			resolve(event.data, event);
		}
      }
    }
  },
  broadcast (channel, data) {

      const broadcast = new BroadcastChannel(channel);

      broadcast.postMessage(data);
      broadcast.close();
  }
};
