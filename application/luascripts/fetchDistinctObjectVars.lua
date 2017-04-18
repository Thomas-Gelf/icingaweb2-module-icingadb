--
-- fetchDistinctObjectVars.lua
--
-- LUA helper script to fetch only distinct object vars from Redis
--
-- This allows to sync then in an efficient way, as it does some deduplication
-- before shipping data over the wire
--

local objectType = KEYS[1]
local objectsKey = 'icinga:config:' .. objectType
local checksumsKey = objectsKey .. ':checksum'
local nextkey
local payload
local varKeys = {}
local vars = {}

local hashes = redis.call("HGETALL", checksumsKey)
for i, v in ipairs(hashes) do
    if i % 2 == 1 then
        nextkey = v
    else
        varKeys[cjson.decode(v)["vars_checksum"]] = nextkey
    end
end

for sum, name in pairs(varKeys) do
    vars[sum] = cjson.decode(redis.call("HGET", objectsKey, name))["vars"]
end

return cjson.encode(vars)
