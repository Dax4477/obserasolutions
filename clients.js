 // clients.js - Master Data File for Obsera Solutions

const OBSERA_CLIENTS = {
    "malabar": {
        name: "MALABAR SPICES",
        tag: "17h Active",
        hours: 17,
        mapLink: "https://maps.app.goo.gl/DstHP7Nfu1285Erc9",
        menuSheetId: "1qVMAfR3Hy-aVYpUam25TnwKy7KmkU4lBV3J7_hM5yio",
        adSheetId: "18dFpLtY3EXUtMNcWUn5ficW1uZWoLw34Geb8lG1JQAE",
        itemsPerSlide: 6
    },

    "bilal": {
        name: "BILAL THATTUKADA",
        tag: "17h Active",
        hours: 17,
        mapLink: "https://maps.google.com/?q=Bilal+Thattukada+Uruvachal",
        menuSheetId: "1s9NCrlKf3i4lt4VL3WKp8A9o9DXYrsE3ccddPVkMyk8",
        adSheetId: "1OhzIcctYTyeKQEA-9K5tFZnQqeEu38r-dHQfvXdby3s",
        itemsPerSlide: 6
    },

  
};
 

const LOCATIONS_DATA = Object.keys(OBSERA_CLIENTS).map(key => {
    return { id: key, ...OBSERA_CLIENTS[key] };
});
