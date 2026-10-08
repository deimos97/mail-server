-- Límites de envío por plan (Fase 4): la web sirve, por cada tier de pago, la lista de buzones activos
-- (solo desde este servidor). ratelimit.conf los usa con filter_map/except_map. Ver portal/config/mail_limits.php.
local lua_selectors = require "lua_selectors"
local lua_maps = require "lua_maps"

local base = "https://unagrandeylibre.es/internal/rspamd/"
for _, tier in ipairs({ "paid", "basic", "pro" }) do
  lua_selectors.maps["ugl_" .. tier] = lua_maps.map_add_from_ucl(base .. tier .. ".map", "set",
    "Buzones del plan " .. tier .. " (unagrandeylibre.es)")
end
