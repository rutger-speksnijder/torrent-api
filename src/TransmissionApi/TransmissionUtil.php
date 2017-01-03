<?php
namespace TransmissionApi;

/**
 * Utility methods for the Transmission API class.
 *
 * @author Rutger Speksnijder.
 * @since transmission-api 1.0.
 */
class TransmissionUtil
{
    /**
     * Converts torrent objects to arrays.
     *
     * @param array $torrents The torrent objects.
     *
     * @return array An array with torrents converted to arrays.
     */
    public static function convertTorrentsToArrays($torrents)
    {
        // Get the mapping for each object type
        $fileMapping = \Transmission\Model\File::getMapping();
        $peerMapping = \Transmission\Model\Peer::getMapping();
        $torrentMapping = \Transmission\Model\Torrent::getMapping();
        $trackerMapping = \Transmission\Model\Tracker::getMapping();
        $trackerStatsMapping = \Transmission\Model\TrackerStats::getMapping();

        // loop through the torrents
        $data = [];
        foreach ($torrents as $torrent) {
            // Get the torrent data
            $torrentData = self::convertObjectToArray($torrent, $torrentMapping);

            // Convert files
            foreach ($torrentData['files'] as $key => $file) {
                $torrentData['files'][$key] = self::convertObjectToArray($file, $fileMapping);
            }

            // Convert peers
            foreach ($torrentData['peers']  as $key => $peer) {
                $torrentData['peers'][$key] = self::convertObjectToArray($peer, $peerMapping);
            }

            // Convert trackers
            foreach ($torrentData['trackers'] as $key => $tracker) {
                $torrentData['trackers'][$key] = self::convertObjectToArray($tracker, $trackerMapping);
            }

            // Convert tracker stats
            foreach ($torrentData['trackerStats'] as $key => $trackerStats) {
                $torrentData['trackerStats'][$key] = self::convertObjectToArray($trackerStats, $trackerStatsMapping);
            }

            // Add the torrent data to the collection
            $data[] = $torrentData;
        }

        // Return the converted torrents
        return $data;
    }

    /**
     * Converts object data to array.
     *
     * @param object $object The object to convert.
     * @param array $mapping The object's mapping array.
     *
     * @return array The data array.
     */
    private static function convertObjectToArray($object, $mapping)
    {
        // Check arguments
        if (!is_object($object) || !is_array($mapping) || empty($mapping)) {
            throw new \InvalidArgumentException("Invalid arguments for convertObjectToArray.");
        }

        // Loop through the object's mapping
        $data = [];
        foreach ($mapping as $dest => $source) {
            // Check if the object has a getProperty method
            $method = 'get' . ucfirst($source);
            if (method_exists($object, $method)) {
                $data[$source] = $object->{$method}();
                continue;
            }

            // Check if the object has an isProperty method
            $method = 'is' . ucfirst($source);
            if (method_exists($object, $method)) {
                $data[$source] = $object->{$method}();
            }
        }

        // Return the data array
        return $data;
    }
}
