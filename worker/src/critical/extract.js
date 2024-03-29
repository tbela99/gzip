import {hash} from '../crypto/hash';
import {ready} from "../utils/ready";

ready(() => {

    let dimension;

    if (!"{CRITICAL_MATCHED_VIEWPORTS}".some(dimension => window.matchMedia('(min-width: ' + dimension.split('x', 1)[0] + 'px)').matches)) {

        for (dimension of "{CRITICAL_DIMENSIONS}") {

            console.info({dimension});

            if (window.matchMedia('(min-width: ' + dimension.split('x', 1)[0] + 'px)').matches) {

                console.info({
                    dimension,
                    matches: true
                });

                const timeout = setInterval(() => {

                    // wait for stylesheets to load
                    if (document.querySelector('link[data-media]')) {

                        return
                    }

                    clearInterval(timeout);

                    const start = performance.now();
                    critical.extract({fonts: true}).then(async (result) => {

                        console.info(`ended critical extraction in ${((performance.now() - start) / 1000).toFixed(3)}s`);
                        console.info(JSON.stringify({stats: result.stats}, null, 1));
                        console.info({result});

                        const extracted = {
                            url: "{CRITICAL_URL}",
                            dimension,
                            // fonts: result.fonts,
                            css: result.styles.concat(result.fonts.map(font => {

                                let css = '@font-face {';

                                for (const entry of Object.entries(font)) {

                                    if (entry[0] == 'properties') {

                                        for (const property of Object.entries(entry[1])) {

                                            css += `${property[0]}: ${property[1]};`
                                        }
                                    }

                                    else {

                                        css += `${entry[0]}: ${entry[1]};`
                                    }
                                }

                                return css + '}';

                            })).join('\n')
                        }

                        const key = "{CRITICAL_HASH}";
                        await fetch("{CRITICAL_POST_URL}", {
                            method: "POST",
                            headers: {
                                'Content-Type': 'application/json; charset=utf-8',
                                'X-Signature': `${key}.${await hash(key + JSON.stringify(extracted), '"{ALGO}"')}`
                            },
                            body: JSON.stringify(extracted)
                        })
                    });

                }, 1000);

                break;
            }
        }
    }
})