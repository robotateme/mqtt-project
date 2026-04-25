local inserted = redis.call('SET', KEYS[1], '1', 'EX', ARGV[1], 'NX')

if not inserted then
    return false
end

return redis.call(
    'XADD',
    KEYS[2],
    'MAXLEN',
    '~',
    ARGV[2],
    '*',
    'event_id',
    ARGV[3],
    'mqtt_topic',
    ARGV[4],
    'payload',
    ARGV[5],
    'received_at',
    ARGV[6],
    'bus_id',
    ARGV[7]
)
